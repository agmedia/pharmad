<?php

namespace App\Models\Back\Orders;

use App\Helpers\Mailchimp;
use App\Helpers\Session\CheckoutSession;
use App\Models\Back\Settings\Settings;
use App\Models\Back\Users\Client;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Bouncer;
use Illuminate\Support\Facades\Log;

class Order extends Model
{

    /**
     * @var string
     */
    protected $table = 'orders';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;


    /**
     * @return mixed
     */
    public function getStatusAttribute()
    {
        return $this->status($this->order_status_id);
    }


    /**
     * @param int $id
     *
     * @return mixed
     */
    public function status(int $id)
    {
        $statuses = Settings::get('order', 'statuses');

        return $statuses->where('id', $id)->first();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id')->with('product');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function history()
    {
        return $this->hasMany(OrderHistory::class, 'order_id')->orderBy('created_at');
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function totals()
    {
        return $this->hasMany(OrderTotal::class, 'order_id')->orderBy('sort_order');
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopePaid($query)
    {
        return $query->where('order_status_id', 3)->orWhere('order_status_id', 4);
    }


    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeLast($query, $count = 9)
    {
        return $query->whereIn('order_status_id', [1,2,3,4,9])->orderBy('created_at', 'desc')->limit($count);
    }


    /**
     * @param       $query
     * @param array $params
     *
     * @return mixed
     */
    public function scopeChartData($query, array $params)
    {
        return $query
            ->whereBetween('created_at', [$params['from'], $params['to']])
            ->orderBy('created_at')
            ->get()
            ->groupBy(function ($val) use ($params) {
                return \Illuminate\Support\Carbon::parse($val->created_at)->format($params['group']);
            });
    }


    /**
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->validate([
            'payment'  => 'required',
            'shipping' => 'required',
            'fname'           => 'required',
            'lname'           => 'required',
            'address'         => 'required',
            'city'            => 'required',
            'state'            => 'required',
            'zip'             => 'required',
            'email'           => 'required',
            'items'           => 'required',
            'sums'            => 'required',
        ]);

        $this->setRequest($request);

        return $this;
    }


    /**
     * @param Request $request
     *
     * @return $this
     */
    public function validateMakeRequest(Request $request)
    {
        $request->validate([
            'order_data'   => 'required',
            'payment'      => 'required',
            'fname'        => 'required',
            'lname'        => 'required',
            'address'      => 'required',
            'zip'          => 'required',
            'city'         => 'required',
            'email'        => 'required',
            'phone'        => 'required',
            'ship_fname'   => 'required',
            'ship_lname'   => 'required',
            'ship_address' => 'required',
            'ship_zip'     => 'required',
            'ship_city'    => 'required',
            'ship_email'   => 'required',
            'ship_phone'   => 'required',
        ]);

        $this->setRequest($request);

        return $this;
    }


    /**
     *
     * @return bool
     */
    public function make()
    {
        $id = $this->insertGetId([
            'user_id'          => auth()->user() ? auth()->user()->id : 0,
            'order_status_id'  => 1,
            'payment_fname'    => $this->request->fname,
            'payment_lname'    => $this->request->lname,
            'payment_address'  => $this->request->address,
            'payment_zip'      => $this->request->zip,
            'payment_city'     => $this->request->city,
            'payment_state'    => $this->request->state,
            'payment_phone'    => $this->request->phone ?: null,
            'payment_email'    => $this->request->email,
            'payment_method'   => $this->request->payment,
            'payment_code'     => null,
            'shipping_fname'   => $this->request->fname,
            'shipping_lname'   => $this->request->lname,
            'shipping_address' => $this->request->address,
            'shipping_zip'     => $this->request->zip,
            'shipping_city'    => $this->request->city,
            'shipping_phone'   => $this->request->phone ?: null,
            'shipping_email'   => $this->request->email,
            'shipping_method'  => $this->request->shipping,
            'shipping_code'    => '',
            'company'          => isset($this->request->company) ? $this->request->company : null,
            'oib'              => isset($this->request->oib) ? $this->request->oib : null,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now()
        ]);

        if ($id) {
            (new OrderProduct())->make($this->request, $id);
            (new OrderTotal())->make($this->request, $id);

            OrderHistory::store($id);

            return $this->find($id);
        }

        return false;
    }


    /**
     * @param null $id
     *
     * @return bool
     */
    public function store($id = null)
    {
        $order = $id ? $this->updateData($id) : $this->storeData();

        if (!$order) {
            return false;
        }

        // 1) Artikli
        OrderProduct::store(json_decode($this->request->items), $order->id);

        // 2) Totals iz forme (ako je JSON razbijen, dobit ćemo null -> napravimo prazan niz)
        $totals = json_decode($this->request->sums, true) ?: [];

        // 3) Ako je poslan iznos dostave, resolve-aj title iz Settings i upiši/override-aj "shipping"
        if ($this->request->filled('shipping_amount')) {
            // title iz postavki za kod dostave
            $shippingSetting = Settings::get('shipping', 'list.' . $this->request->shipping)->first();
            $shippingTitle   = $shippingSetting ? $shippingSetting->title : 'Dostava';

            // normalizacija broja (podržava i "55,00")
            $shippingValue = (float) str_replace(',', '.', $this->request->shipping_amount);

            $found = false;
            foreach ($totals as &$t) {
                // podrži i array i object
                $code = is_array($t) ? ($t['code'] ?? null) : ($t->code ?? null);
                if ($code === 'shipping') {
                    if (is_array($t)) {
                        $t['value'] = $shippingValue;
                        $t['title'] = $t['title'] ?? ($t['name'] ?? $shippingTitle);
                    } else {
                        $t->value = $shippingValue;
                        $t->title = $t->title ?? ($t->name ?? $shippingTitle);
                    }
                    $found = true;
                    break;
                }
            }
            unset($t);

            if (!$found) {
                // dodaj shipping red
                $totals[] = [
                    'code'  => 'shipping',
                    'title' => $shippingTitle,
                    'value' => $shippingValue,
                ];
            }
        }

        // 3b) Ako je payment COD i poslan iznos naknade, upiši/override-aj "payment"
        if (strtolower($this->request->payment) === 'cod' && $this->request->filled('payment_amount')) {
            $paymentTitle = 'Naknada za pouzeće'; // po potrebi promijeni ili povuci iz configa
            $paymentValue = (float) str_replace(',', '.', $this->request->payment_amount);

            $foundPayment = false;
            foreach ($totals as &$t) {
                $code = is_array($t) ? ($t['code'] ?? null) : ($t->code ?? null);
                if ($code === 'payment') {
                    if (is_array($t)) {
                        $t['value'] = $paymentValue;
                        $t['title'] = $t['title'] ?? ($t['name'] ?? $paymentTitle);
                    } else {
                        $t->value = $paymentValue;
                        $t->title = $t->title ?? ($t->name ?? $paymentTitle);
                    }
                    $foundPayment = true;
                    break;
                }
            }
            unset($t);

            if (!$foundPayment) {
                $totals[] = [
                    'code'  => 'payment',
                    'title' => $paymentTitle,
                    'value' => $paymentValue,
                ];
            }
        } else {
            // (Opcionalno) ako payment NIJE COD, ukloni eventualni 'payment' red iz totals
            $totals = array_values(array_filter($totals, function ($t) {
                $code = is_array($t) ? ($t['code'] ?? null) : ($t->code ?? null);
                return $code !== 'payment';
            }));
        }

        // 4) Spremi totals
        OrderTotal::store($totals, $order->id);

        return $order;
    }


    /**
     * @return bool
     */
    private function storeData()
    {
        $payment = Settings::get('payment', 'list.' . $this->request->payment)->first();
        $shipping = Settings::get('shipping', 'list.' . $this->request->shipping)->first();

        $id = $this->insertGetId([
            'payment_fname'    => $this->request->fname,
            'payment_lname'    => $this->request->lname,
            'payment_address'  => $this->request->address,
            'payment_zip'      => $this->request->zip,
            'payment_city'     => $this->request->city,
            'payment_state'    => $this->request->state,
            'payment_phone'    => $this->request->phone ?: null,
            'payment_email'    => $this->request->email,
            'payment_method'   => $payment->title,
            'payment_code'     => $payment->code,
            'shipping_fname'   => $this->request->fname,
            'shipping_lname'   => $this->request->lname,
            'shipping_address' => $this->request->address,
            'shipping_zip'     => $this->request->zip,
            'shipping_city'    => $this->request->city,
            'shipping_phone'   => $this->request->phone ?: null,
            'shipping_email'   => $this->request->email,
            'shipping_method'  => $shipping->title,
            'shipping_code'    => $shipping->code,
            'company'          => isset($this->request->company) ? $this->request->company : null,
            'oib'              => isset($this->request->oib) ? $this->request->oib : null,
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now()
        ]);

        if ($id) {
            OrderHistory::store($id);

            return $this->find($id);
        }

        return false;
    }


    /**
     * @param $id
     *
     * @return bool
     */
    private function updateData($id)
    {
        $payment = Settings::get('payment', 'list.' . $this->request->payment)->first();
        $shipping = Settings::get('shipping', 'list.' . $this->request->shipping)->first();

        $updated = $this->where('id', $id)->update([
            'payment_fname'    => $this->request->fname,
            'payment_lname'    => $this->request->lname,
            'payment_address'  => $this->request->address,
            'payment_zip'      => $this->request->zip,
            'payment_city'     => $this->request->city,
            'payment_state'    => $this->request->state,
            'payment_phone'    => $this->request->phone ?: null,
            'payment_email'    => $this->request->email,
            'payment_method'   => $payment->title,
            'payment_code'     => $payment->code,
            'shipping_fname'   => $this->request->fname,
            'shipping_lname'   => $this->request->lname,
            'shipping_address' => $this->request->address,
            'shipping_zip'     => $this->request->zip,
            'shipping_city'    => $this->request->city,
            'shipping_phone'   => $this->request->phone ?: null,
            'shipping_email'   => $this->request->email,
            'shipping_method'  => $shipping->title,
            'shipping_code'    => $shipping->code,
            'company'          => isset($this->request->company) ? $this->request->company : null,
            'oib'              => isset($this->request->oib) ? $this->request->oib : null,
            'updated_at'       => Carbon::now()
        ]);

        if ($updated) {
            $order = $this->find($id);

            $request = new Request([
                'status' => 0,
                'comment' => 'Izmjenjeni podaci narudžbe.!'
            ]);

            OrderHistory::store($id, $request);

            return $order;
        }

        return false;
    }


    /**
     * Set Model request variable.
     *
     * @param $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }


    /**
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function getLatest($count = 15)
    {
        $query = (new Order())->newQuery();

        return $query->with('status')->orderBy('id', 'desc')->limit($count)->get();
    }


    /**
     * @param Request $request
     *
     * @return Builder
     */
    public function filter(Request $request): Builder
    {
        $query = $this->newQuery()->with('products');

        if ($request->has('status')) {
            $query->where('order_status_id', '=', $request->input('status'));
        }

        if ($request->has('search') && ! empty($request->input('search'))) {
            $query->where(function ($query) use ($request) {
                return $query->where('id', 'like', '%' . $request->input('search') . '%')
                             ->orWhere('payment_fname', 'like', '%' . $request->input('search') . '%')
                             ->orWhere('payment_lname', 'like', '%' . $request->input('search') . '%')
                             ->orWhere('payment_email', 'like', '%' . $request->input('search') . '%')
                             ->orWhereHas('products', function ($query) use ($request) {
                                 $query->where('name', 'like', '%' . $request->input('search') . '%');
                             });
            });
        }

        return $query->orderBy('created_at', 'desc');
    }


    /**
     * @param int    $order_id
     * @param string $comment
     *
     * @return bool
     */
    public function storeHistory(int $order_id, string $comment, int $status = 1): bool
    {
        return OrderHistory::store($order_id, new Request(['comment' => $comment, 'status' => $status]));
    }

}
