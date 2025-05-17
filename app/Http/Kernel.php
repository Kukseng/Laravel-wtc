protected $middlewareAliases = [
'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
// Other default middleware...

// Your custom middleware
'admin' => \App\Http\Middleware\AdminMiddleware::class,
'warehouse' => \App\Http\Middleware\WarehouseManagerMiddleware::class,
'staff' => \App\Http\Middleware\StaffMiddleware::class,
];