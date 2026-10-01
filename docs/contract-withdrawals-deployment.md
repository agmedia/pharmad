# Puštanje funkcije raskida ugovora na cPanel

## Baza podataka

Primijeniti samo jedan postupak:

- preporučeno: `php artisan migrate --force`
- ako Artisan migracije nisu dostupne: jednom pokrenuti `database/025_extend_contract_withdrawals.sql`

SQL skripta pretpostavlja da tablica iz `database/024_create_contract_withdrawals.sql` već postoji. Prije ručnog SQL zahvata napraviti sigurnosnu kopiju baze.

## Konfiguracija

Provjeriti produkcijske vrijednosti:

- `APP_URL=https://ljekarne-pharmad.hr`
- `GOOGLE_RECAPTCHA_SITE_KEY` i `GOOGLE_RECAPTCHA_SECRET_KEY`
- `GOOGLE_RECAPTCHA_HOSTNAME=ljekarne-pharmad.hr` ako se produkcijska domena razlikuje od hosta u `APP_URL`
- SMTP postavke `MAIL_*`
- adresu primatelja u Administracija → Postavke → Jednostrani raskid

Nakon promjene konfiguracije pokrenuti `php artisan config:clear` ili ponovno izgraditi produkcijski config cache.

## Scheduler

Laravel scheduler mora se pokretati svake minute. U cPanel Cron Jobs dodati naredbu prilagođenu stvarnoj PHP binarnoj datoteci i putanji projekta:

```cron
* * * * * /putanja/do/php /putanja/do/projekta/artisan schedule:run >> /dev/null 2>&1
```

Scheduler svakih pet minuta ponovno pokušava poslati samo neuspjele potvrde, najviše pet automatskih pokušaja po primatelju. Ručni pokušaj ostaje dostupan u administrativnom detalju zahtjeva.

## Završna provjera

1. Otvoriti `/raskid-ugovora` kao neprijavljen korisnik.
2. Poslati po jedan test za cijelu narudžbu i odabrane proizvode.
3. Potvrditi da kupac i PharmAD prime e-mail sa sadržajem, referencom te datumom i vremenom.
4. Potvrditi da se oba zahtjeva vide u administraciji i da kupac nema pristup administrativnim rutama.
