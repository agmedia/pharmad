<?php

namespace App\Http\Livewire\Front\Checkout;

use App\Models\Front\Cart\CartSession;
use App\Models\Front\Catalog\Product;
use Livewire\Component;

/**
 *
 */
class Cart extends Component
{

    /**
     * @var \App\Models\Front\Cart\Cart
     */
    protected $cart;

    /**
     * @var
     */
    protected $items;

    /**
     * @var
     */
    public $subtotal;

    /**
     * @var
     */
    public $total;

    /**
     * @var
     */
    public $count;


    /**
     * @return void
     */
    public function mount()
    {}


    /**
     * @param int $product_id
     *
     * @return void
     */
    public function removeItemFromCart(int $product_id)
    {
        $this->setCart();

        $this->cart->remove($product_id);

        $this->cart->resetMethods_IfNeeded();

        return redirect()->to(request()->server('HTTP_REFERER'));
    }


    /**
     * @param int $product_id
     * @param int $quantity
     *
     * @return void
     */
    public function changeItemQuantity(int $product_id, int $quantity)
    {
        $this->setCart();

        $items = $this->getItems();
        $product = Product::query()->find($product_id);

        foreach ($items as $item) {
            if ($item['id'] == $product_id) {
                if (($item['quantity'] + $quantity) <= $product->quantity) {
                    $this->cart->add($product, $quantity);

                    $this->emit('updateCartNavIcon', $this->cart->get()['count']);
                }
            }
        }
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->setCart();

        //dd(Product::query()->find(3)->coupon());

        return view('livewire.front.checkout.cart', [
            'cart' => $this->cart->get(),
            'items' => $this->getItems()
        ]);
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @return void
     */
    private function setCart()
    {
        $this->cart = CartSession::resolve();
    }


    /**
     * @return mixed
     */
    private function getItems()
    {
        return $this->cart->get()['items']->sortBy('name')/*->toArray()*/;
    }
}
