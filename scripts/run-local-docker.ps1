#Requires -Version 5.0
$ErrorActionPreference = "Stop"

$RootDir = Split-Path -Parent $PSScriptRoot
Set-Location $RootDir

if (-not $env:COMPOSE_CMD) {
    $ComposeCmd = "docker compose"
} else {
    $ComposeCmd = $env:COMPOSE_CMD
}

$SeedPasswordFile = "config/seed_password_local.php"
if (-not (Test-Path $SeedPasswordFile)) {
    Write-Error "Missing seed password file: $SeedPasswordFile`nCopy it first:`n  Copy-Item config/seed_password_local.php.example config/seed_password_local.php"
}

$WithTests = $false
$Mode = "all"
foreach ($arg in $args) {
    if ($arg -eq "--with-tests") { $WithTests = $true }
    switch ($arg) {
        "--seed-users" { $Mode = "users" }
        "--seed-albums" { $Mode = "albums" }
        "--seed-photos" { $Mode = "photos" }
        "--seed-all" { $Mode = "all" }
    }
}

Write-Host "Starting containers..."
Invoke-Expression "$ComposeCmd up -d --build"

Invoke-Expression "$ComposeCmd exec -T php sh -lc 'test -f vendor/autoload.php'"
if ($LASTEXITCODE -ne 0) {
    Write-Host "Installing composer dependencies (vendor/autoload.php missing in container)..."
    Invoke-Expression "$ComposeCmd exec -T php sh -lc 'git config --global --add safe.directory /var/www/html || true; composer install --no-interaction'"
}

Write-Host "Migrating main DB..."
Invoke-Expression "$ComposeCmd exec -T php php yii migrate --interactive=0"

$SeedUsersPrefix = if ($env:SEED_USERS_PREFIX) { $env:SEED_USERS_PREFIX } else { "user_" }
$SeedUsersCount = if ($env:SEED_USERS_COUNT) { $env:SEED_USERS_COUNT } else { "10" }
$SeedAlbumsPerUser = if ($env:SEED_ALBUMS_PER_USER) { $env:SEED_ALBUMS_PER_USER } else { "10" }
$SeedPhotosPerAlbum = if ($env:SEED_PHOTOS_PER_ALBUM) { $env:SEED_PHOTOS_PER_ALBUM } else { "10" }

switch ($Mode) {
    "users" {
        Write-Host "Seeding users: ${SeedUsersPrefix}1..${SeedUsersPrefix}${SeedUsersCount}"
        Invoke-Expression "$ComposeCmd exec -T php php yii seed/users --prefix=$SeedUsersPrefix --count=$SeedUsersCount"
    }
    "albums" {
        Write-Host "Seeding albums: $SeedAlbumsPerUser per user (expects user_1..user_10)"
        Invoke-Expression "$ComposeCmd exec -T php php yii seed/albums --per-user=$SeedAlbumsPerUser"
    }
    "photos" {
        Write-Host "Seeding photos: $SeedPhotosPerAlbum per album"
        Invoke-Expression "$ComposeCmd exec -T php php yii seed/photos --per-album=$SeedPhotosPerAlbum"
    }
    "all" {
        Write-Host "Seeding demo data (10 users, 100 albums, 1000 photos)..."
        Invoke-Expression "$ComposeCmd exec -T php php yii seed/all"
    }
    default {
        Write-Error "Unknown mode: $Mode"
    }
}

if ($Mode -eq "all" -or $WithTests) {
    Write-Host "Migrating test DB (for Codeception)..."
    Invoke-Expression "$ComposeCmd exec -T -e MYSQL_DATABASE=yii2basic_test php php yii migrate --interactive=0"
}

if ($WithTests) {
    Write-Host "Running unit tests..."
    Invoke-Expression "$ComposeCmd exec -T php vendor/bin/codecept run unit"
    Write-Host "Running functional API tests..."
    Invoke-Expression "$ComposeCmd exec -T php vendor/bin/codecept run functional ApiCest.php"
}

Write-Host "Done."
