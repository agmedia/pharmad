@extends('front.layouts.app')

@section('content')




    <nav class="mb-4 text-center text-lg-start" aria-label="breadcrumb">
        <ol class="breadcrumb flex-lg-nowrap">
            <li class="breadcrumb-item"><a class="text-nowrap" href="{{ route('index') }}"><i class="ci-home"></i>Naslovna</a></li>
            <li class="breadcrumb-item text-nowrap active" aria-current="page">Naše ljekarne</li>
        </ol>
    </nav>


    <section class="d-md-flex justify-content-between align-items-center mb-4 pb-2">
        <h1 class="h2 mb-3 mb-md-0 me-3">Naše ljekarne</h1>

    </section>



    <!-- Contact detail cards-->
    <section class=" pt-grid-gutter">
        <div class="row">



            <div class="col-12  mb-5">

                <h4>001 Prigorje Brdovečko</h4>

                <p>Zagrebačka 99, 10291 Prigorje Brdovečko<br />
                    <strong>TEL:</strong> <a href="tel:0038514678893">01/ 46 78 893</a></p>
                <iframe allowfullscreen="" aria-hidden="false" frameborder="0" height="450" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2777.5985198582816!2d15.725572116226028!3d45.87934141366611!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765c8980502a3ab%3A0x400285e992bfd8c6!2sLjekarne%20PharmAD%20Prigorje%20Brdove%C4%8Dko!5e0!3m2!1shr!2shr!4v1612183287277!5m2!1shr!2shr" style="border:0;" tabindex="0" width="100%"></iframe>

                <h4><br />
                    002 Šenkovec</h4>

                <p>Zagrebačka 61, 10292 Šenkovec<br />
                    <strong>TEL:</strong> <a href="tel:0038513396511">01/ 33 96 511</a></p>
                <iframe allowfullscreen="" aria-hidden="false" frameborder="0" height="450" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2777.056463104886!2d15.687339716226285!3d45.890183812937586!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765c976513b871d%3A0xf606c67a5b8b81cd!2sLjekarne%20PharmAD%20Harmica!5e0!3m2!1shr!2shr!4v1612183764864!5m2!1shr!2shr" style="border:0;" tabindex="0" width="100%"></iframe>

                <h4><br />
                    003 Kraljevec na Sutli</h4>

                <p>Kraljevec na Sutli 131, 49294 Kraljevec na Sutli<br />
                    <strong>TEL:</strong> <a href="tel:0038549289004 ">049/ 289 004 </a></p>
                <iframe allowfullscreen="" aria-hidden="false" frameborder="0" height="450" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5544.3310568376555!2d15.721483280024986!3d45.987924170952965!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765c1a168a3bda3%3A0xc5c7851a4bd22923!2sLjekarne%20PharmAD%20Kraljevec%20na%20Sutli!5e0!3m2!1shr!2shr!4v1612183797443!5m2!1shr!2shr" style="border:0;" tabindex="0" width="100%"></iframe>

                <h4><br />
                    004 Zabok</h4>

                <p>Dubrava Zabočka 90B, 49210 Zabok<br />
                    <strong>TEL:</strong> <a href="tel:0038549220000">049/ 220 000</a></p>
                <iframe allowfullscreen="" aria-hidden="false" frameborder="0" height="450" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2770.4657700012563!2d15.949684816228503!3d46.021854404079356!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4765e7fd5b127113%3A0xac81f85e634bf134!2sLjekarne%20PharmAD%20Zabok!5e0!3m2!1shr!2shr!4v1612183825241!5m2!1shr!2shr" style="border:0;" tabindex="0" width="100%"></iframe>




            </div>


        </div>
    </section>







@endsection


