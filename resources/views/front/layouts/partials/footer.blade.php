@if (request()->routeIs(['naplata']) or request()->routeIs(['pregled']))


    <footer class="footer pt-5">



        <div class="bg-light mt-2 pt-0">
            <div class="d-sm-flex justify-content-between align-items-center mx-auto px-4 py-1" >
                <div class="fs-sm text-dark opacity-50 text-center text-sm-start py-3">2025. Ljekarne Pharmad © Sva prava pridržana. Web by <a class="text-dark" href="https://www.agmedia.hr" target="_blank" rel="noopener">AG media</a></div>
                <div class="widget widget-links widget-dark  text-center text-md-end"><img src="{{ asset('media/cards/visa.svg') }}" alt="Visa" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/maestro.svg') }}" alt="Maestro" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/mastercard.svg') }}" alt="MasterCard" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35"> <img src="{{ asset('media/cards/diners.svg') }}" alt="Diners" class="d-inline-block" style="width: 55px; margin-right: 3px;" width="55" height="35">
                    <img src="{{ config('settings.images_domain') }}media/cards/google_pay.svg" width="55" height="35" alt="Gogole pay" class="d-inline-block" style="width: 55px; margin-right: 3px;"><img src="h{{ config('settings.images_domain') }}media/cards/apple_pay.svg" width="55" height="35" alt="Apple Pay" class="d-inline-block" style="width: 55px; margin-right: 3px;">
                </div>
            </div>
        </div>
    </footer>

@else

    <section class="col">
        <div class="card py-5 border-0 " style="background-image: url({{ config('settings.script_domain') . '../media/img/baka.png' }});background-repeat: repeat;">
            <div class="card-body py-md-4 py-3 px-4 text-center">
                <h3 class="mb-3">Ne propusti akciju!</h3>
                <p class="mb-4 pb-2">Prijavi se na naš Newsletter i budi u toku sa najnovijim akcijama i novostima!</p>

                <div class="widget mx-auto" style="max-width: 500px;">
                    @include('front.layouts.partials.session')
                    <form class="subscription-form " action="#" method="post"  novalidate>
                        @csrf
                        <div class="input-group flex-nowrap"><i class="ci-mail position-absolute top-50 translate-middle-y text-muted fs-base ms-3"></i>
                            <input class="form-control rounded-start" type="text" value="" name="email" placeholder="Vaša emil adresa" required>
                            <button class="btn btn-primary" type="submit" >Prijavi se</button>
                        </div>

                        <div class="form-text mt-3">* Prijavom na Newsletter pristajem na uvjete korištenja i dajem privolu za primanje promotivnih obavijesti.</div>

                    </form>
                </div>
            </div>
        </div>
    </section>


    <footer class="footer bg-light mt-0 pt-3" >

        <div class="px-lg-5 pt-2 pb-4">
            <div class="mx-auto px-3" >

                <div class="row pt-3 ">
                    <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter">
                        <div class="d-inline-flex align-items-center text-start"><i class="ci-truck text-ph" style="font-size: 3rem;"></i>
                            <div class="ps-3">
                                <p class="text-dark fw-bold fs-base mb-1">Brza dostava</p>
                                <p class="text-dark fs-ms opacity-70 mb-0">Unutar 5 radnih dana</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter">
                        <div class="d-inline-flex align-items-center text-start"><i class="ci-security-check text-ph" style="font-size: 3rem;"></i>
                            <div class="ps-3">
                                <p class="text-dark fw-bold fs-base mb-1">Sigurna kupovina</p>
                                <p class="text-dark fs-ms opacity-70 mb-0">SSL certifitikat i CorvusPay</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter">
                        <div class="d-inline-flex align-items-center text-start"><i class="ci-bag text-ph" style="font-size: 3rem;"></i>
                            <div class="ps-3">
                                <p class="text-dark fw-bold fs-base mb-1">Besplatna dostava</p>
                                <p class="text-dark fs-ms opacity-70 mb-0">Za narudžbe iznad 80€</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6 col-6 mb-grid-gutter">
                        <div class="d-inline-flex align-items-center text-start"><i class="ci-locked text-ph" style="font-size: 3rem;"></i>
                            <div class="ps-3">
                                <p class="text-dark fw-bold fs-base mb-1">Zaštita kupca</p>
                                <p class="text-dark fs-ms opacity-70 mb-0">Zaštita svih podataka</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="hr-dark mb-2">
                <div class="row py-4">
                    <div class="col-md-4  text-center text-md-start mb-4">

                        <h3 class="widget-title fw-700 d-block text-dark"><span>Korisnička podrška</span></h3>

                        <p class=" text-dark  fs-md pb-1 d-block">  <strong>Broj telefona</strong><br>
                            +385 (0) 99 489 1210</p>

                        <p class=" text-dark  fs-md pb-1 d-block">  <strong>Email</strong><br>
                            webshop@ljekarne-pharmad.hr</p>

                        <p class=" text-dark  fs-md pb-1 d-block">  <strong>Radno vrijeme</strong><br>
                            Pon-Pet: 8-16

                        </p>


                        <div class="widget mt-4 text-md-nowrap text-center text-sm-start">
                            <a class=" btn btn-outline-primary btn-sm btn-icon" href="https://www.instagram.com/zuziobrt/"><i class="ci-instagram"></i></a>
                            <a class="btn btn-outline-primary btn-sm btn-icon" href="https://www.facebook.com/zuziobrt/"><i class="ci-facebook"></i></a>
                        </div>
                    </div>
                    <!-- Mobile dropdown menu (visible on screens below md)-->
                    <div class="col-12 d-md-none text-center mb-sm-4 pb-2">
                        <div class="btn-group dropdown d-block mx-auto mb-3">
                            <button class="btn btn-outline-dark border-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">Uvjeti kupnje</button>
                            <ul class="dropdown-menu my-1">
                                @foreach ($uvjeti_kupnje as $page)
                                    <li><a class="dropdown-item" href="{{ route('catalog.route.page', ['page' => $page]) }}">{{ $page->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <!-- Desktop menu (visible on screens above md)-->
                    <div class="col-md-4 d-none d-md-block text-center text-md-start mb-4">
                        <div class="widget widget-links widget-dark pb-2">
                            <h3 class="widget-title fw-700 text-dark"><span>Uvjeti kupnje</span></h3>
                            <ul class="widget-list">
                                @foreach ($uvjeti_kupnje as $page)
                                    <li class="widget-list-item"><a class="widget-list-link" href="{{ route('catalog.route.page', ['page' => $page]) }}">{{ $page->title }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4 d-none d-md-block text-center text-md-start mb-4">
                        <div class="widget widget-links widget-dark pb-2">
                            <h3 class="widget-title fw-700 text-dark"><span>Načini plaćanja</span></h3>
                            <ul class="widget-list  ">
                                <li class="widget-list-item"><a href="https://www.zuzi.hr/info/nacini-placanja" class="widget-list-link" > kreditnom karticom jednokratno ili na rate</a></li>
                                <li class="widget-list-item"><a href="https://www.zuzi.hr/info/nacini-placanja" class="widget-list-link" > virmanom / općom uplatnicom / internet bankarstvom</a></li>
                                <li class="widget-list-item"><a href="https://www.zuzi.hr/info/nacini-placanja" class="widget-list-link" >gotovinom prilikom pouzeća</a></li>

                                <li class="widget-list-item"><a href="https://www.zuzi.hr/info/nacini-placanja" class="widget-list-link" >osobno preuzimanje i plaćanje u antikvarijatu</a></li>
                            </ul>

                        </div>
                    </div>
                </div>

                <div class="d-md-flex justify-content-between pt-2">
                    <div class="pb-4 fs-sm text-dark  text-center text-md-start">© 2025. Ljekarne Pharmad. Web by <a class="text-dark" title="Izrada web shopa - B2C ili B2B web trgovina - AG media" href="https://www.agmedia.hr/usluge/izrada-web-shopa/" target="_blank" rel="noopener">AG media</a>
                    </div>
                    <div class="widget widget-links widget-light pb-4 text-center text-md-end">
                        <img class="d-inline-block" style="width: 55px;margin-right:3px" src="{{ config('settings.images_domain') }}media/cards/visa.svg" width="55" height="35" alt="Visa"/>
                        <img class="d-inline-block" style="width: 55px;margin-right:3px" src="{{ config('settings.images_domain') }}media/cards/maestro.svg" width="55" height="35" alt="Maestro"/>
                        <img class="d-inline-block" style="width: 55px;margin-right:3px" src="{{ config('settings.images_domain') }}media/cards/mastercard.svg" width="55" height="35" alt="MasterCard"/>
                        <img class="d-inline-block" style="width: 55px;margin-right:3px" src="{{ config('settings.images_domain') }}media/cards/diners.svg" width="55" height="35" alt="Diners"/>
                        <img src="{{ config('settings.images_domain') }}media/cards/google_pay.svg" width="55" height="35" alt="Gogole pay" class="d-inline-block" style="width: 55px; margin-right: 3px;"><img src="{{ config('settings.images_domain') }}media/cards/apple_pay.svg" width="55" height="35" alt="Apple Pay" class="d-inline-block" style="width: 55px; margin-right: 3px;">
                    </div>
                </div>



            </div>
        </div>


    </footer>



@endif






