<?php

namespace App\Models\Back\Catalog;

use App\Models\Back\Settings\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Options extends Model
{

    /**
     * @var string
     */
    protected $table = 'options';

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @var Request
     */
    protected $request;


    /**
     * @param $value
     *
     * @return mixed
     */
    public function getGroupAttribute($value)
    {
        return $this->grupa;
    }


    /**
     * Validate new category Request.
     *
     * @param Request $request
     *
     * @return $this
     */
    public function validateRequest(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'type'  => 'required',
            'item'  => 'required'
        ]);

        $this->request = $request;

        return $this;
    }


    /**
     * Store new category.
     *
     * @return false
     */
    public function create()
    {
        $group = $this->request->input('title');

        foreach ($this->request->input('item') as $item) {
            $id = $this->insertGetId([
                'grupa'      => Str::slug($group),
                'ime_grupe'  => $group,
                'title'      => $this->request->input('title'),
                'type'       => $this->request->input('type'),
                'value'      => $item['color'] ?? '#000000',
                'value_opt'  => $item['color_opt'] ?? null,
                'option_sku' => $item['option_sku'] ?? null,
                'data'       => '',
                'sort_order' => $item['sort_order'] ?? 0,
                'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            if ( ! $id) {
                return false;
            }
        }

        return $this->find($id);
    }


    /**
     * @param Category $category
     *
     * @return false
     */
    public function edit()
    {
        dd($this->request->toArray());
        $values = Options::query()->where('grupa', Str::slug($this->grupa))->get();
        $group  = $this->request->input('title');
        $items  = collect($this->request->input('item'));

        foreach ($values as $value) {
            $item = $items->where('id', $value->id);

            if ( ! empty($item->first())) {
                $saved = $value->update([
                    'grupa'      => Str::slug($group),
                    'ime_grupe'  => $group,
                    'title'      => $this->request->input('title'),
                    'type'       => $this->request->input('type'),
                    'value'      => $item['color'] ?? '#000000',
                    'value_opt'  => $item['color_opt'] ?? null,
                    'option_sku' => $item['option_sku'] ?? null,
                    'data'       => '',
                    'sort_order' => $item->first()['sort_order'] ?? 0,
                    'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                    'updated_at' => Carbon::now()
                ]);

                if ( ! $saved) {
                    return false;
                }
            }
        }

        foreach ($items->where('id', '==', '0') as $item) {
            $id = $this->insertGetId([
                'grupa'      => Str::slug($group),
                'ime_grupe'  => $group,
                'title'      => $this->request->input('title'),
                'type'       => $this->request->input('type'),
                'value'      => $item['color'] ?? '#000000',
                'value_opt'  => $item['color_opt'] ?? null,
                'option_sku' => $item['option_sku'] ?? null,
                'data'       => '',
                'sort_order' => $item['sort_order'] ?? 0,
                'status'     => (isset($this->request->status) and $this->request->status == 'on') ? 1 : 0,
                'updated_at' => Carbon::now()
            ]);

            if ( ! $id) {
                return false;
            }
        }

        if ($items->count() < $values->count()) {
            $diff = $values->diffUsing($items, function ($one, $other) {
                return $other['id'] - $one['id'];
            });

            if ($diff->count()) {
                foreach ($diff as $item) {
                    Options::query()->where('id', $item['id'])->delete();
                }
            }
        }

        return true;
    }


    /**
     * @return array
     */
    public function getList()
    {
        $response = [];
        $values   = Options::query()->orderBy('sort_order', 'asc')->get();

        foreach ($values as $value) {
            $response[$value->grupa]['group']   = $value->grupa;
            $response[$value->grupa]['items'][] = [
                'id'         => $value->id,
                'title'      => $value->title,
                'value'      => $value->color,
                'option_sku' => $value->option_sku,
                'value_opt'  => $value->color_opt,
                'sort_order' => $value->sort_order
            ];
        }

        return $response;
    }


    /**
     * @return array
     */
    public static function getColorList()
    {
        $response = [];
        $values   = Options::query()->where('type', 'color')->orderBy('title', 'asc')->get();

        foreach ($values as $value) {
            $response[] = [
                'id'         => $value->id,
                'title'      => $value->title,
                'value'      => $value->color,
                'value_opt'  => $value->color_opt,
                'option_sku' => $value->option_sku,
                'sort_order' => $value->sort_order
            ];
        }
        $response = collect($response)->sortBy('title')->toArray();

        return $response;
    }
}
