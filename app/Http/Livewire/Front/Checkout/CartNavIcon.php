<?php

namespace App\Http\Livewire\Front\Checkout;

use App\Models\Front\Cart\CartSession;
use App\Models\Front\Catalog\Product;
use Livewire\Component;

/**
 *
 */
class CartNavIcon extends Component
{

    /**
     * @var
     */
    protected $cart;

    /**
     * @var int
     */
    public $count = 0;

    /**
     * @var string[]
     */
    protected $listeners = ['addCartItem'];


    /**
     * @return void
     */
    public function mount()
    {
        $this->setCart();
        $this->count = $this->cart->get()['count'];
    }


    /**
     * @param Product $product
     * @param int     $quantity
     *
     * @return void
     */
    public function addCartItem(Product $product, int $quantity)
    {
        $this->setCart();

        $this->cart->add($product, $quantity);

        $this->cart->resetMethods_IfNeeded();

        $this->count = $this->cart->get()['count'];
    }


    /**
     * @param int $product_id
     *
     * @return void
     */
    public function removeFromCart(int $product_id)
    {
        $this->setCart();

        $this->cart->remove($product_id);

        $this->cart->resetMethods_IfNeeded();

        return redirect()->to(request()->server('HTTP_REFERER'));
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        $this->setCart();

        return view('livewire.front.checkout.cart-nav-icon', [
            'cart' => $this->cart
        ]);
    }


    /**
     * @return void
     */
    private function setCart()
    {
        $this->cart = CartSession::resolve();
    }
}
