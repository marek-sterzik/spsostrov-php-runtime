# Submitter

Submitter je aplikace určená pro formální odevzdávání různých druhů prací (souborů) ve školním prostředí. Primárně je designována pro SPŠ Ostrov.

## Závislosti

Aplikace v sobě obsahuje komplet vývojové prostředí. Je postavená na platformě [spsostrov-php-runtime](https://github.com/marek-sterzik/spsostrov-php-runtime).

Aplikace potřebuje ke svému běhu `docker` a standardní unixové prostředí (bash a další základní příkazy). Lze tak použít jakoukoliv moderní
distribuci GNU/Linux, nebo lze `docker` nainstalovat i na Windows a s prostředím WSL (Windows subsystem for Linux) poté provozovat aplikaci
v rámci lokálně nainstalované linuxové distribuce (Ubuntu).

Bližší informace lze nalézt v [README.md](https://github.com/marek-sterzik/spsostrov-php-runtime/blob/master/README.md) souboru prostředí
spsostrov-php-runtime nebo v souboru [README.windows.md](https://github.com/marek-sterzik/spsostrov-php-runtime/blob/master/README.windows.md).

## Rychlý start

Pro rychlý start systému, spusťte tuto sekvenci příkazů:

```
bin/docker configure
bin/docker up
bin/docker initialize
```

**Aplikaci přitom vždy ze zásady spouštíme jako obyčejný uživatel, nikdy jako root!**

Aplikace se potom rozběhne na portu, který jste zadali v konfigurační části. Pokud jste ponechali základní port 80, budete mít aplikaci
k dispozici na adrese:

```
http://localhost
```

Pokud jste zadali jiné číslo portu, bude aplikace dostupná na adrese:

```
http://localhost:<port>
```

## Testovací data

Pro nahrání testovacích dat do databáze lze spustit příkaz:
```
bin/fixtures load
```

Pro opačný proces uložení testovacích dat z databáze do souboru slouží příkaz:
```
bin/fixtures save
```
