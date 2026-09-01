<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $baslik }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 16px; margin-bottom: 2px; }
        .altbaslik { color: #6b7280; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .saat { color: #9ca3af; font-size: 9px; }
        .bos { color: #d1d5db; }
    </style>
</head>
<body>
    <h1>{{ $baslik }}</h1>
    <div class="altbaslik">{{ $altBaslik }} · Toplam {{ $toplamSaat }} ders saati</div>

    @if (empty($gunler))
        <p>Aktif eğitim günü tanımlı değil.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Saat</th>
                    @foreach ($gunler as $gunAdi)
                        <th>{{ $gunAdi }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($slotlar as $slot)
                    <tr>
                        <td>{{ $slot }}. ders</td>
                        @foreach ($gunler as $gunNo => $gunAdi)
                            @php $hucre = $izgara[$gunNo][$slot] ?? null; @endphp
                            <td>
                                @if ($hucre)
                                    <div>
                                        {{ $hucre['ust'] }}
                                        @if ($hucre['izinli'] ?? false)
                                            <strong>(İzinli)</strong>
                                        @endif
                                    </div>
                                    <div class="saat">{{ $hucre['alt'] }}</div>
                                    <div class="saat">
                                        {{ $saatler[$gunNo][$slot]['baslangic'] ?? '' }}-{{ $saatler[$gunNo][$slot]['bitis'] ?? '' }}
                                    </div>
                                @else
                                    <span class="bos">boş</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
