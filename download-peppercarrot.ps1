$baseUrl = "https://peppercarrot.com/0_sources"
$outputRoot = "C:\xampp\htdocs\komikbyte\assets\comics\pepper-carrot"

# Get the official episode index
$episodes = Invoke-RestMethod `
    -Uri "$baseUrl/episodes.json"

foreach ($episode in $episodes) {

    $dir = $episode.name
    $totalPages = [int]$episode.total_pages

    # Extract episode number from ep01_...
    if ($dir -match "^ep(\d+)_") {
        $episodeNumber = [int]$matches[1]
    }
    else {
        continue
    }

    $chapterFolder = "ch{0:D2}" -f $episodeNumber
    $outputFolder = Join-Path $outputRoot $chapterFolder

    New-Item -ItemType Directory -Force -Path $outputFolder | Out-Null

    Write-Host ""
    Write-Host "Downloading Episode $episodeNumber - $dir"
    Write-Host "Pages: $totalPages"

    # Download cover
    $coverUrl = "$baseUrl/$dir/low-res/en_Pepper-and-Carrot_by-David-Revoy_E{0:D2}.jpg" -f $episodeNumber
    $coverPath = Join-Path $outputFolder "cover.jpg"

    try {
        Invoke-WebRequest `
            -Uri $coverUrl `
            -OutFile $coverPath `
            -ErrorAction Stop

        Write-Host "Downloaded cover"
    }
    catch {
        Write-Host "Cover not found, continuing..."
    }

    # Download pages
    for ($page = 0; $page -le $totalPages; $page++) {

        $pageNumber = "{0:D2}" -f $page

        $url = "$baseUrl/$dir/low-res/en_Pepper-and-Carrot_by-David-Revoy_E{0:D2}P{1}.jpg" `
            -f $episodeNumber, $pageNumber

        $fileName = "page-{0:D2}.jpg" -f ($page + 1)
        $filePath = Join-Path $outputFolder $fileName

        try {
            Invoke-WebRequest `
                -Uri $url `
                -OutFile $filePath `
                -ErrorAction Stop

            Write-Host "  Page $($page + 1) downloaded"
        }
        catch {
            Write-Host "  Page $($page + 1) not found"
        }
    }
}

Write-Host ""
Write-Host "======================================"
Write-Host "DOWNLOAD COMPLETE"
Write-Host "======================================"
Write-Host "Files saved to:"
Write-Host $outputRoot