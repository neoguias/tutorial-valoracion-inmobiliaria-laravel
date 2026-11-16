# Valoración de inmuebles por comparables con Laravel

El CRM inmobiliario de Atalaya Inmobiliaria, una agencia inventada de Valencia, con un módulo nuevo que estima el precio de un piso a partir de las ventas parecidas de su barrio. Busca los comparables, los iguala en ascensor y terraza, traza una recta de regresión del precio sobre la superficie y devuelve la cifra, una horquilla y la lista de ventas con las que se ha calculado.

Está hecho con Laravel 13 y Livewire 4, sobre el CRM del tutorial anterior, que usa los paquetes [Wiretables](https://github.com/edulazaro/wiretables), [Wiremodal](https://github.com/edulazaro/wiremodal), [Wirepicker](https://github.com/edulazaro/wirepicker), [Wiretoast](https://github.com/edulazaro/wiretoast) y [Larasearch](https://github.com/edulazaro/larasearch).

El tutorial completo está en Neoguias: [Cómo valorar un piso por comparables en un CRM inmobiliario con Laravel](https://www.neoguias.com/valorar-inmuebles-comparables-laravel/). Hay una versión con Next.js en [este otro repositorio](https://github.com/neoguias/tutorial-valoracion-inmobiliaria-nextjs).

Los tutoriales anteriores de la serie:

- [Cómo crear un CRM inmobiliario con Laravel](https://www.neoguias.com/crear-crm-inmobiliario-laravel/), con [su repositorio](https://github.com/neoguias/tutorial-crm-inmobiliario-laravel).
- [Cómo crear un portal inmobiliario como Idealista con Laravel](https://www.neoguias.com/crear-portal-inmobiliario-laravel/), con [su repositorio](https://github.com/neoguias/tutorial-portal-inmobiliario-laravel).

## Cómo arrancarlo

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

Abre `http://localhost:8000`. El seeder carga diez inmuebles con una foto cada uno, cinco clientes con lo que buscan, dos visitas y 360 compraventas de ejemplo de los dos últimos años, que son los comparables de la valoración. La valoración está en `http://localhost:8000/valorar`, y cada ficha de inmueble tiene un botón para valorarlo. Las fotos de ejemplo están generadas con IA y son de uso libre.

Los tests usan una base de datos SQLite en memoria:

```bash
php artisan test
```

Requiere PHP 8.3 y Node 20 o superior.

## Dónde está cada cosa

- [`app/Models/Property.php`](app/Models/Property.php) y [`app/Models/Demand.php`](app/Models/Demand.php): los inmuebles y lo que busca cada cliente. El scope `matching` de cada uno hace el cruce, en un sentido y en el otro.
- [`app/Valuation/Valuator.php`](app/Valuation/Valuator.php): la valoración, con la búsqueda de comparables, el ajuste por ascensor y terraza y la regresión.
- [`app/Models/Sale.php`](app/Models/Sale.php) y [`database/seeders/SaleSeeder.php`](database/seeders/SaleSeeder.php): las compraventas cerradas que hacen de comparables.
- [`app/Models/PropertyPhoto.php`](app/Models/PropertyPhoto.php): las fotos de cada inmueble, guardadas en el disco `public`. La primera es la portada.
- [`app/Enums`](app/Enums): operación, tipo de inmueble, estado y resultado de una visita, con sus textos en español.
- [`app/Livewire/Forms`](app/Livewire/Forms): los formularios de alta de inmuebles y de clientes, con su validación.
- [`resources/views/pages`](resources/views/pages): las pantallas del CRM, que son los inmuebles, la ficha de un inmueble, los clientes, las visitas y la valoración.
- [`resources/css/app.css`](resources/css/app.css) y [`resources/js/app.js`](resources/js/app.js): los estilos y scripts de los paquetes de la interfaz.
- [`config/crm.php`](config/crm.php): el nombre de la agencia y las zonas en las que trabaja.
- [`tests/Feature/ValuationTest.php`](tests/Feature/ValuationTest.php): la valoración con ventas a medida, para comprobar las cifras a mano.
- [`tests/Feature/CrmTest.php`](tests/Feature/CrmTest.php): el cruce, los formularios, los buscadores, el orden por precio y la agenda.

## Autor

Creado por [Edu Lázaro](https://edulazaro.com) para [Neoguias](https://www.neoguias.com).

## Licencia

Este ejemplo es software libre con [licencia MIT](LICENSE).
