$ErrorActionPreference = "Stop"

$backendPath = $PSScriptRoot
$uploadTempPath = Join-Path $backendPath "storage/app/upload-tmp"
$publicPath = Join-Path $backendPath "public"
$routerPath = Join-Path $backendPath "vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"

New-Item -ItemType Directory -Path $uploadTempPath -Force | Out-Null

if (-not (Test-Path -LiteralPath $routerPath)) {
    throw "Nie znaleziono routera Laravel. Uruchom ten skrypt z zainstalowanymi zależnościami backendu."
}

Push-Location $publicPath
try {
    php -d "upload_tmp_dir=$uploadTempPath" -S localhost:8000 $routerPath
}
finally {
    Pop-Location
}
