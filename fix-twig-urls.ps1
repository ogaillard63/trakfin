# Script de correction finale des URLs Twig
$files = Get-ChildItem -Path "c:\laragon\www\trakfin\templates" -Filter "*.twig" -Recurse

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    
    # Remplacer url('/path/{{ var }}') par url('/path/' ~ var)
    $content = $content -replace "url\('([^']*)\{\{\s*([^}]+)\s*\}\}([^']*)'\)", "url('`$1' ~ `$2 ~ '`$3')"
    
    # Nettoyer les ~ '' ~ vides
    $content = $content -replace "\s*~\s*''\s*~\s*", " ~ "
    $content = $content -replace "\s*~\s*''\s*\)", ")"
    $content = $content -replace "url\(''\s*~\s*", "url('"
    
    Set-Content -Path $file.FullName -Value $content -NoNewline
    Write-Host "Corrigé: $($file.Name)"
}

Write-Host "`nTerminé! Toutes les URLs ont été corrigées."
