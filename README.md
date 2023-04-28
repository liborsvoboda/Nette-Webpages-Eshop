# Mibron.store developer guide

## Installation
1. Clone / fork code, import database.
2. Open `app/config/local.neon.example`, edit database setting and rename it to `app/config/local.neon`.
3. Run `composer install` to load PHP dependencies.
4. Run `npm i` to install frontend dependencies (**node v11.x required**).
5. Run `gulp build` to compile frontend assets.

It's recommended to develop on root-level domain like `http://mibron.test`. Use `dnsmasq` for this, on Mac OS check this manual: https://getgrav.org/blog/macos-catalina-apache-mysql-vhost-apc

**Configuration files**
`app/config/local.neon` is ignored by git, configuration in this file is only for your development version.
`app/config/common.neon` is main project conif file. It's good place to start when creating new project.


## Gulp environment

**Node of version 11.x is required**
Use `n` node version manager (https://www.npmjs.com/package/n).

All frontend assets are managed by **Gulp**. It handles SASS, JS, images and SVG compilation and optimalization. There is also **npm** as package manager.

### Directory structure
All assets are stored in `www/assets/` directory. There you'll find following:

- `front/` Root directory for e-shop frontend assets
	- `js/` All files here are compiled using babel, use ES6 or whatever babel compiles
	- `scss/` Source SCSS are watched and compiled on every change
	- `img/` Directory for bitmap images, which are watched and optimalized on every change
	- `svg/` Directory to put SVG, files are watched and optimized on every change
	- `build/` Directory for compiled files. ***NEVER ADD ANY FILE HERE***, content of this directory is deleted out before each build.
- `admin/` Root directory for e-shop admin section
	- `img/` Images for admin section
	- `js/` Admin JavaScript, no bable or npm here yet (use only **ES5** here)
	- `scss/` Source SCSS for admin section
	- `css/` Compiled CSS from SCSS

### Gulp workflow
To make development process as fast as possible, there are two stages of files compilation. 

#### Development stage
```
gulp watch
```
This command will watch all SCSS, JS, images and SVGs and will perform quick compilation whenever one of these files are changed, added or deleted. Compiled resources are then stored to `build/dev/` directory. 

- Sass files are only compiled, they aren't prefixed nor minimalized
- JS files are only compiled, they aren't minimalized
- Images are optimized
- SVGs are optimized
- Build directory isn't cleared, any deleted sourcefiles will remain here

#### Production stage
```
gulp build
```
This command will prepare production assets. You should run this before (or as part of) every deployment process.

1. Build directory is completely deleted
2. SASS files are compiled without prefinig and minimalization
3. 3rd party CSS and JS files are copied
4. Images and SVGs are copied and optimized
3. SASS files are prefixed and minimalized
4. JS files are minimalized

### Install external JS / CSS library
All front-end dependencies should be installed using `npm` as a **dev dependency**. Ie. to install Bootstrap, use this command:

```
npm install --save-dev bootstrap
```
or shorter version:

```
npm i -D bootstrap
```

#### Link installed asset to public directory
In template assets should be linked from either `front/build/dev/` directory when in dev mode, or `front/build/min/` directory when in production mode. There is a boolean parameter `%tpl.distAssets%` in `app/config/common.neon`, which can be overwritten in `app/config/local.neon`. This parameter is used to decide, from which directory assets should be loaded.
To link assets in template, you can use `App\Model\Services\TplSettingsService::getAssetPath()` method, which does all previously mentioned decisions and also add version param so you don't neeed to worry about browser cache:

```
// @layout.latte
<link rel="stylesheet" href="{$basePath}{$tplSetting->getAssetPath('css/style.css')}">
<script src="{$basePath}{$tplSetting->getAssetPath('js/app.js')}"></script>
```

External libraries are stored in `node_modules`, which isn't accessible by the browser, so it's neccessary to link assets manually.

**SCSS** can be linked using `@import`, no need to copy files. Check root stylesheet in `front/scss/style.scss`, Bootstrap SASS files are linked there.

**CSS** can't be only imported, they need to be copied first. In `gulpfile.js` add desired 3rd party CSS file to `copyCss` array, than you can import it from `vendor/` directory.
Take *glightbox* library for example:

```
// gulpfile.js
...
const copyCss = [
	...
	'node_modules/glightbox/dist/css/glightbox.min.css'
];


// style.scss
...
@import 'vendor/glightbox.min.css';
```

**JS** files from 3rd party libraries also need to be copied using `copyJs` array in `gulpfile.js`.
For example:

```
// gulpfile.js
const copyJs = [
	...
	'vendor/nette/forms/src/assets/netteForms.js'
];


// @layout.latte
...
<script src="{$basePath}{$tplSetting->getAssetPath('js/vendor/netteForms.js')}"></script>
...
```

CZ:

Nově v nabídce "Naprosto unikátní EASY SYSTEM BUILDER" 
absolutně snadná tvorba 3vrstvého systému (LIBOVOLNÁ DB, BACKEND-SERVER, FRONTEND-KLIENT) se znalostmi excelu
Stačí vytvořit Tabulku pro Data a Formulář, vše v grafických designerech
Systém si tedy doslova naklikáte, Každý den 1 tabulka = za měsíc prodejní systém i s objednávkami a nabídkami
A TO ANI NÁHODOU NENÍ VŠE.
Nemusí to být pouze systém Datový, ale bez probléMu i multimediální, na stříhání videí, práce s fotkami či 3D
nebo Dokonce SYSTÉM ŘÍDÍCÍ pro ovládání strojů (Aktuálně je podpora PLC SIEMENS), nebo libovolného jiného Hardware
po dodání specifik. 
Či dále systém Kontrolní, Zálohovací, DataWarehouse, Controling, BI OLAP, Flow Procesy, 
Výrobní, Informační či business dotykové Terminály.  TAKÉ JE MOŽNÉ VYUŽÍT JEN JAKO NADSTAVBU, ČI SPOJENÍ SYSTÉMŮ

Zahajovací náklad za jádro je pouhých 10 000Kč / 400Euro , a můžete si zkoušet sami či levně doobjednat
co tam budete chtít přidat. Neohýbejte se před systémem vy, POŘIĎTE SI SYSTÉM PŘESNĚ NA MÍRU.
Pro představu:
 - fakturační systém s OBJ+NAB+MULTIPOBOČKY,SKLAD,POKLADNA                        - 14 DNÍ
 - implementace dotykového terminálu do výroby pro výkazy práce                   - 4 DNY
 - Právěš vzniká systém pro hotely včetně pokojové agengy,fakturace a objednávek  - 1 MĚSÍC
 - Updaty Zdarma, 1 vývoj pro všechny klienty přes sdílené agendy projektu v kódu
 
 Neomezený počet uživatelů, neomezený tisk, neomezené možnosti, návody, tipy, triky, rady,
 Připraveno pro všechny typy systémů a terminálů pro MS WINDOWS
 
INTELIGENTNÍ DOKUMENTACE: https://liborsvoboda.github.io/EASYSYSTEM-EASYSERVER-CZ/  
KÓD: https://github.com/liborsvoboda/EASYSYSTEM-EASYSERVER-CZ  
ONLINE UKÁZKA: https://kliknetezde.cz  

 TAK NEOTÁLEJTE A OZVĚTE SE JEŠTĚ DNES NEŽ PŮJDE CENA NAHORU
 BACKEND SERVER JE OŽNÉ POUŽÍT SAMOSTATNĚ PRO LIBOVOLNÝ JINÝ MULTIPLATFORMNÍ PROJEKT
 
 IT Architekt
 Libor Svoboda GroupWare-Solution.Eu
 Tel: 00420 724986873, email: Libor.Svoboda@GroupWare-Solution.Eu
 
 
 
----------------------------------------------------------------------------------------
EN: 
 
New in the menu "Absolutely unique EASY SYSTEM BUILDER"
absolutely easy to create a 3-tier system (LIBOVOLNÁ DB, BACKEND-SERVER, FRONTEND-KLIENT) with excel knowledge
Just create a Table for Data and a Form, all in graphic designers
So you literally click on the system, 1 table every day = sales system with orders and offers per month
And that's not all, by any chance.
It doesn't have to be only a data system, but also a multimedia one without any problem, for cutting videos, working with photos or 3D
or even a CONTROL SYSTEM for machine control (Currently SIEMENS PLC is supported), or any other Hardware
after delivery of specifications.
Or the Control system, Backup, DataWarehouse, Controlling, BI OLAP, Flow Processes,
Production, Information or business touch Terminals. IT CAN ALSO BE USED ONLY AS AN EXTENSION OR CONNECTION OF SYSTEMS

The starting cost for the core is only 10,000 CZK / 400 Euro, and you can try it yourself or order cheaply
what you want to add there. Don't bow down to the system, GET A CUSTOMIZED SYSTEM.
For idea:
  - invoicing system with OBJ+NAB+MULTI BRANCHES, WAREHOUSE, CHECKOUT - 14 DAYS
  - implementation of a touch terminal in production for work reports - 4 DAYS
  - A system for hotels including room agency, invoicing and orders is currently being created - 1 MONTH
  - Free updates, 1 development for all clients via shared project agendas in code
 
  Unlimited users, unlimited printing, unlimited options, tutorials, tips, tricks, advice,
  Prepared for all types of systems and terminals for MS WINDOWS
 
INTELLIGENT DOCUMENTATION: https://liborsvoboda.github.io/EASYSYSTEM-EASYSERVER-EN/  
CODE: https://github.com/liborsvoboda/EASYSYSTEM-EASYSERVER-EN  
ONLINE EXAMPLE: https://kliknetezde.cz  

  SO DON'T DELAY AND CALL TODAY BEFORE THE PRICE GOES UP
  BACKEND SERVER CAN BE USED INDEPENDENTLY FOR ANY OTHER MULTIPLATFORM PROJECT

  IT Architect
  Libor Svoboda GroupWare-Solution.Eu
  Tel: 00420 724986873, email: Libor.Svoboda@GroupWare-Solution.Eu
