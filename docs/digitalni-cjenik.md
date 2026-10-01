# Sidrene cijene i digitalni XML cjenik

## Produkcijska instalacija

1. Napraviti sigurnosnu kopiju baze.
2. Pokrenuti `php artisan migrate --force`. Ako se migracije na tom poslužitelju ne koriste, izvršiti `database/026_add_anchor_prices_and_price_lists.sql` točno jednom.
   Alternativno, Superadmin može na nadzornoj ploči kliknuti **Instaliraj digitalni cjenik**. Gumb je idempotentan: dodaje samo stupce/tablicu koji nedostaju, popunjava samo prazne sidrene vrijednosti i ne prepisuje već unesene sidrene cijene.
3. Osigurati da PHP korisnik može pisati u `storage/app`; aplikacija sama stvara poddirektorij `storage/app/price-lists` s dozvolom `0775`.
4. U administratorskom sučelju otvoriti **Postavke → Digitalni cjenik**, provjeriti identitet objekta i generirati probnu verziju.
5. Na serveru mora postojati jedna cron stavka koja svake minute pokreće Laravel scheduler:

   ```cron
   * * * * * cd /putanja/do/aplikacije && php artisan schedule:run >> /dev/null 2>&1
   ```

Scheduler pokreće `php artisan price-list:generate` svakog dana u podešeno vrijeme (početno 07:00) u zoni `Europe/Zagreb`, uz zaštitu od paralelnog izvođenja. Neuspjelo generiranje ne dira posljednju valjanu verziju. Arhiva se čisti tek nakon uspješne objave i nikada se ne postavlja kraće od 30 dana (početno 35).

## Početni podaci i sinkronizacija

Migracija svim postojećim proizvodima postavlja `anchor_price = price` i `anchor_date = 2026-09-10`. Novi proizvodi bez ručno zadanih sidrenih podataka dobivaju početnu redovnu cijenu i datum uvrštenja. Postojeći PharmAD XML tok mijenja redovnu/akcijsku cijenu i zalihu, ali ne mijenja sidrene podatke. Vanjski XML uvoz sidrenih cijena nije uključen u ovu fazu.

Datum 10.09.2026. i preslikavanje trenutačne redovne cijene predstavljaju poslovno zadanu početnu vrijednost. Prije produkcijske objave treba potvrditi primjenjivost tog datuma i iznosa, osobito za proizvode koji su ranije bili obuhvaćeni propisom.

## CSV format

Obvezna zaglavlja su `sku`, `sidrena_cijena`, `referentni_datum`; opcionalna su `jedinica_mjere`, `cijena_po_jedinici`. Razdjelnik može biti zarez ili točka-zarez, decimalni razdjelnik zarez ili točka, a datum `YYYY-MM-DD`, `DD.MM.YYYY` ili `DD.MM.YYYY.`. CSV ne stvara proizvode. Neispravni, duplicirani i nepoznati SKU redci preskaču se i nude u zasebnom CSV izvještaju.

## Javni URL-ovi

- `/cjenik`
- `/cjenik/aktualni.xml`
- `/cjenik/arhiva/{filename}`

XML datoteke ostaju izvan javnog web direktorija i poslužuju se isključivo kroz provjereni zapis arhive. Odgovori sadrže `application/xml`, UTF-8, `Last-Modified` i ETag.
