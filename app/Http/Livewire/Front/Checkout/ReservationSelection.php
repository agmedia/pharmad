<?php

namespace App\Http\Livewire\Front\Checkout;

use App\Models\Front\Checkout\Reservation;
use Livewire\Component;

class ReservationSelection extends Component
{
    public $days = [];
    public $hours = [];
    public $selected_day = '';
    public $selected_hour = '';


    public function mount()
    {
        $this->days = Reservation::getUpcomingDays();
        $this->hours = Reservation::getHoursList();

        //dd($this->hours);

        if (session()->has('selected_reservation')) {
            //dd(session()->get('selected_reservation'));
        }
    }


    public function updatingSelectedDay($value)
    {
        $this->selected_day = $value;

        //dd($this->selected_day);

        $this->hours = Reservation::getHoursList($this->selected_day);
    }


    public function createReservationSession()
    {
        if (empty($this->selected_day) || empty($this->selected_hour)) {
            return redirect()->to(request()->server('HTTP_REFERER'))->with('error', 'Niste odabrali datum montaže. Molimo odaberite dan i vrijeme.');

        } else {
            session()->put('selected_reservation', [
                'day' => $this->selected_day,
                'hour' => $this->selected_hour,
            ]);
        }
    }


    public function render()
    {
        return view('livewire.front.checkout.reservation-selection');
    }
}
