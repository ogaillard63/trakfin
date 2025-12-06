# Script de correction complète des URLs dans les templates Twig
$files = Get-ChildItem -Path "c:\laragon\www\trakfin\templates" -Filter "*.twig" -Recurse

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    
    # Remplacer app_url ~ '/path' par url('/path')
    $content = $content -replace "app_url\s*~\s*'([^']+)'", "url('`$1')"
    $content = $content -replace 'app_url\s*~\s*"([^"]+)"', 'url("$1")'
    
    # Remplacer {{ app_url }}/path par {{ url('/path') }}
    $content = $content -replace '\{\{\s*app_url\s*\}\}/([^"''}\s]+)', '{{ url(''/$1'') }}'
    
    # Remplacer {{ app_url }}/ par {{ url('/') }}
    $content = $content -replace '\{\{\s*app_url\s*\}\}/', '{{ url(''/'') }}'
    
    Set-Content -Path $file.FullName -Value $content -NoNewline
    Write-Host "Corrigé: $($file.Name)"
}

Write-Host "`nTerminé! Tous les templates ont été corrigés."
