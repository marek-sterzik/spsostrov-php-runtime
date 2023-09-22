# Zprovoznění dockeru a závislostí spsostrov-php-runtime na Windows

Výojové prostředí spsostrov-php-runtime běží na Windows v rámci subsystému WSL (Windows subsystem for Linux). Nevýhoda je, že celý WSL subsystém je stále experimentální a při instalaci se mnoho věcí může pokazit.

Toto je nejnovější optimalizovaný návod, jak správně nainstalovat všechny potřebné závislosti na provoz prostředí. Návod předpokládá Windows 10 a novější.

## Postup instalace

G1. Instalace Docker Desktop
2. Aktualizace WSL subsystému na nejnovější verzi
3. Instalace Ubuntu
4. Integrace Ubuntu s Dockerem

**Kroky je potřeba provádět postupně a nepřecházet na další krok, dokud není úspěšně hotový předchozí.**

### Instalace Docker Desktop

Je potřeba standardní cestou stáhnout a naistalovat aplikaci Docker desktop ze stránek projektu:

https://docs.docker.com/desktop/install/windows-install/

Instalace vyžaduje oprávnění administrátora. Každý uživatel, který bude docker používat, by měl být přidán do skupiny `docker-users`.

V rámci instalace Docker Desktop by instalátor měl zároveň nabídnout možnost zapnutí WSL subsystému.


### Aktualizace WSL subsystému na nejnovější verzi


Ve Windows PowerShellu je potřeba spustit následující příkazy a hlídat u nich správné provedení. Pokud příkaz nic nevypíše, znamená to úspěch, pokud něco vypíše, je potřeba zkontrolovat obsah výpisu, zda obsahuje informaci o chybě nebo o úspěšném dokončení. **Příkazy, které skončí chybou je potřeba opakovat do té doby, dokud se je nepovede úspěšně dokončit.**

Nejprve je potřeba aktualizovat systém WSL na nejnovější verzi zavoláním následujícího příkazu:

```
wsl --update
```

Příkaz někdy odmítá zahájit zahájit stahování. Pokud se objeví ukazatel procent, tak už se stahování úspěšně zahájilo. Pokud se ukazatel procent ale nechce objevit, lze po čase možné příkaz restartovat pomocí stisku Ctrl+C a jeho nového spuštění. V některých případech se jeví, že se stahování nezahájí **nikdy**. Je to ale jenom zdání. Zde Windows store skutečně funguje náhodně a někdy se stahování podaří zahájit až po několika hodinách pokusů.

Nicméně někdy může být úspěšné aktualizovat WSL příkazem:

```
wsl --update --web-download
```

který obchází stahování přes Windows store. I tam to ale může skončit neúspěchem. V této verzi příkazu ale dojde k případnému neúspěchu **až po stahování**. Pokud se tímto příkazem nedaří aktualizovat WSL, je potřeba to zkoušet tím prvním příkazem. Jakékoliv nápady, jak tento krok zkrátit a jak jej učinit více deteriministický, jsou vítány.

Po úspěšné aktualizaci WSL je ještě potřeba zavolat příkaz:

```
wsl --set-default-version 2
```

Ten už ale není problematický a nebývá s ním problém.

### Instalace Ubuntu

Ve Windows store si najdeme aplikaci Ubuntu (je jedno jaká verze, ale ideálně ta nejnovější) a nainstalujeme jí. Problém zde ovšem opět je, že se instalace mnohdy nechce vůbec rozběhnout. Pokud se instalace nechce rozběhnout, lze zkusit tyto tři varianty:

1. Nechat tomu víc času (někdy se instalace rozběhne až po delším čase sama).
2. Vypnout a zapnout Windows store aplikaci a po restartu instalaci spustit znovu.
3. Zkusit ruční instalaci podle následujícího návodu.

Ruční instalaci lze potom provést takto:

1. Stáhnout soubor `https://aka.ms/wslubuntu2204` a uložit ho jako `Ubuntu.appx`.
2. V PowerShellu zavolat příkaz `Add-AppxPackage <cesta-k-souboru>\Ubuntu.appx`. Pokud by byl například soubor uložen jako `C:\Ubuntu.appx`, zavolal by se příkaz `Add-AppxPackage C:\Ubuntu.appx`

Pokud se aplikaci podaří nainstalovat, další kroky už by opět měly být neproblematické. Je ale potřeba ještě udělat následující:

1. V nabídce start najdeme nainstalovanou aplikaci Ubuntu a spustíme jí.
2. Objeví se terminál. Při prvním spuštění bude na terminálu vidět čekání na dokončení instalace systému.
3. Po dokončení instalace vás terminál vyzve na zadání uživatelského jména a hesla. Toto uživatelské jméno a heslo se bude platné v ráci nainstalovaného Ubuntu.
4. Po úspěšném dokončení celé instalace by se měl objevit standardní bash prompt známý ze světa Linuxu.

Po úspěšném spuštění terminálu ještě zkontrolujeme příkazem v PowerShellu `wsl --list --verbose`, že nainstalované Ubuntu má nastavenou verzi WSL na 2 (Verze WSL 1 je nepoužitelná). V případě, že Ubuntu běží přecijenom pod WSL 1, lze jej zkonvertovat na WSL 2 pomocí PowerShell příkazu:

```
wsl --set-version <přesný-název-distribuce> 2
```

Např.:

```
wsl --set-version Ubuntu 2
```

### Integrace Ubuntu s Dockerem

Zapneme aplikaci Docker Desktop.

* Při prvním spuštění **odmítneme žádost o registraci** a přeskočíme úvodní kroky.
* Poté stiskneme ikonu zubatého kola (nastavení) vpravo nahoře.
* V nastavení vybereme volbu `Resources -> WSL Integration`.
* Tam se musí zapnout volba pro nainstalované Ubuntu pod nadpisem "Enable integration with additional distros."
* Pokud Ubuntu v seznamu chybí, znamená to, že se nezdařil správně nějaký z předchozích kroků.
* Nakonec potvrdíme a uložíme změny.
* Celé okno aplikace Docker Desktop potom můžeme zavřít (Docker stejně běží na pozadí a má ikonku na systémové liště, skrze kterou je stále ovladatelný)

Nakonec ještě ukončíme terminál Ubuntu a znovu ho spustíme, aby se uživatel dostal do skupiny `docker`. To potom zkontrolujeme zavoláním příkazu `group`, který by (mimo jiné) měl skupinu docker ukázat. Pokud skupina docker chybí, opět zřejmě selhal nějaký z předchozích kroků.

Tím je instalace jako celek dokončena.


## Provoz systému

Po dokončení instalace už není potřeba výše zmíněné kroky znovu opakovat. Chceme-li dosáhnout (např. po restartu počítače) fungujícího systému, stačí provést dvě operace:

1. Zapneme aplikaci Docker desktop (okno můžeme klidně zavřít a můžeme jí nechat klidně běžet jenom na pozadí v systémové liště)
2. Zapneme aplikaci Ubuntu.
3. V terminálu aplikace ubuntu provozujeme prostředí spsostrov-php-runtime podle [základního návodu](README.md).
4. Je-li v systému nainstalováno Visual Studio Code, můžeme jej v terminálu Ubuntu spouštět příkazem `code <adresář-nebo-soubor> &`.
5. Pokud se ze systému odhlašujeme, **je nutné docker zase vypnout**. Jinak docker zůstane běžet na pozadí a nově přihlášený uživatel se k němu nedostane a nemůže si ani zapnout vlastní docker službu.
