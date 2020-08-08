# SPŠ Ostrov PHP runtime environment

Toto je vývojové prostředí pro vývoj PHP aplikací v předmětu PVA.

## Závislosti

Pro běh vývojového prostředí je potřeba operační systém založený na linuxu. Prostředí je založeno na systému docker, proto musí být nainstalovány ještě tyto zvláštní balíky:

* docker
* docker-compose

## Rychlý start

Pro rychlý start systému, spusťte tuto sekvenci příkazů:

```
bin/docker configure
bin/docker up
bin/docker initialize
```

Aplikace se potom rozběhne na portu, který jste zadali v konfigurační části. Pokud jste ponechali základní port 80, budete mít aplikaci
k dispozici na adrese:

```
http://localhost
```

Pokud jste zadali jiné číslo portu, bude aplikace dostupná na adrese:

```
http://localhost:<port>
```

## Konfigurace prostředí

Prostředí se konfiguruje příkazem

```
bin/docker configure
```

Po spuštění příkazu budete dotázáni na různé otázky.  V základním prostředí se nastavuje pouze číslo portu, na kterém bude webová aplikace poslouchat.

Prostředí stačí nakonfigurovat jednou před prvním spuštěním. V případě, nutnosti prostředí rekonfigurovat, je potřeba následně restartovat docker kontejnery pomocí dvojice příkazů `bin/docker restart`.

## Spuštění a vypnutí kontejnerů

Kontejnery spustíte příkazem:
```
bin/docker up
```
A zastavíte je příkazem:
```
bin/docker down
```
Popřípadě je můžete "odstřelit" příkazem:
```
bin/docker kill
```

Chcete-li kontejnery restartovat (tj. vypnout a zapnout), můžete použít příkaz:
```
bin/docker restart
```

Běh kontejnerů je nutný, aby celá webová aplikace fungovala. Kontejnery obsahují veškerý software jako php procesor, popřípadě webový server.

## Inicializace aplikace

Aplikaci inicializujete příkazem:
```
bin/docker initialize
```
Význam tohoto kroku je připravit aplikaci k běhu systému. Systém je vytvořen tak, aby se do systému daly snadno přidávat nové inicializační kroky.

Inicializaci je potřeba také provést pouze jednou, ale v případě některých změn může být potřeba aplikaci reinicializovat. Zejména jde například o změnu závislostí v souboru `composer.json`.

## Další užitečné příkazy

Chcete-li spustit `docker-compose` v rámci prostředí aplikace, spusťte příkaz:

```
bin/docker compose
```
(tento příkaz je vhodný pouze pro pokročilé využití)

Pokud chcete spustit nějaký příkaz v rámci daného docker kontejneru, můžete jej spustit příkazem:

```
bin/docker exec <kontejner> <příkaz> <argumenty>
```
Opět, tento příkaz je určen pouze pro pokročilé uživatele. V základním prostředí je definován jediný kontejner se jménem `webserver`, který obsahuje webserver a prostředí pro běh php.

## Adresáře a soubory

Celé prosředí obsahuje tyto adresáře:

* `bin` - místo pro skripty, které ovlivňují vývojové prostředí. V základní variantě obsahuje adresář pouze skript `docker`. Lze ale přidávat další příkazy pro různé účely.
* `docker` - obsahuje definice docker kontejnerů. Jen pro pokročilé uživatelé.
* `lib` - obsahuje pomocné soubory, které nejsou určeny k editaci.
* `public` - obsahuje soubory, které jsou přímo viditelné přes webový server (statické soubory)
* `scripts` - obsahuje skripty, které se používají ke konfiguraci a inicializaci prostředí. (do obou skriptů je možné přidávat další funkce)
* `src` - obsahuje zdrojový kód celé aplikace
* `templates` - může obsahovat různé šablony pro vytváření obsahu (v základní verzi prostředí není význam nijak definován)
* `vendor` - o tento adresář se stará composer a nemělo by se do něj zasahovat ručně, pouze voláním composeru.

Dále pak obsahuje tyto soubory:

* `.config.env` - obsahuje konfiguraci celého prostředí, která byla vytvořena příkazem `bin/docker configure`. Soubor není přítomen, dokud se prostředí nezkonfiguruje.
* `.gitignore` - soubor říkající systému `git`, které soubory a adresáře se nemají ukládat do repozitáře.
* `README.md` - tento soubor s nápovědou
* `composer.json` - konfigurační soubor pro composer.
* `composer.lock` - pomocný stavový soubor pro composer (není určen pro přímou editaci)
* `docker-compose.yml` - konfigurační soubor pro docker-compose - definuje kontejnery, které v rámci systému poběží.