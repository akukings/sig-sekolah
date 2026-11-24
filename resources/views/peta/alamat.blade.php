@extends('layouts.public')

@section('title', 'Cek Lokasi')

@section('content')
<div class="toolbar">
    Klik di mana saja pada peta untuk melihat alamat dan sekolah terdekat.

    <button type="button" id="lokasi-saya"
        style="margin-left: 10px; padding: 8px 12px;
               background: #2563eb; color: white;
               border: none; border-radius: 6px; cursor: pointer;">
        Lokasi saya
    </button>

</div>

<div id="peta" class="peta"></div>

<div id="hasil" class="panel"
     style="top: 64px; right: 12px; width: 320px; max-height: 70%;
            overflow: auto; display: none;"></div>
@endsection

@push('scripts')
<script>
    const map = Peta.buat('peta');
    const lapisan = L.layerGroup().addTo(map);
    const panel = document.getElementById('hasil');

    let semua = [];
    let terakhir = 0;

    Peta.ambilSekolah().then((fitur) => {
        semua = fitur;
    });

    // Mencari n sekolah terdekat dari sebuah titik [lat, lng]
    function terdekat(titik, n = 3) {
        return semua
            .map((f) => ({
                fitur: f,
                jarak: Peta.jarakKm(titik, Peta.latlng(f))
            }))
            .sort((a, b) => a.jarak - b.jarak)
            .slice(0, n);
    }

    async function ambilAlamat(lat, lng) {
        const url = 'https://nominatim.openstreetmap.org/reverse'
            + '?format=jsonv2&addressdetails=1&accept-language=id'
            + `&lat=${lat}&lon=${lng}`;

        const respon = await fetch(url);

        if (!respon.ok) {
            throw new Error('HTTP ' + respon.status);
        }

        return respon.json();
    }

    map.on('click', async (e) => {
        // Kebijakan Nominatim: maksimal 1 permintaan per detik
        const sekarang = Date.now();

        if (sekarang - terakhir < 1200) {
            return;
        }

        terakhir = sekarang;

        const { lat, lng } = e.latlng;
        const titik = [lat, lng];

        lapisan.clearLayers();

        L.circleMarker(titik, {
            radius: 9,
            color: '#fff',
            weight: 3,
            fillColor: '#ef4444',
            fillOpacity: 1,
        }).addTo(lapisan);

        const dekat = terdekat(titik);

        dekat.forEach((d) => {
            L.polyline([titik, Peta.latlng(d.fitur)], {
                color: '#2563eb',
                weight: 2,
                dashArray: '6 6',
            }).addTo(lapisan);
        });

        panel.style.display = 'block';
        panel.textContent = 'Mencari alamat...';

        let bagianAlamat = '<em>Alamat tidak dapat dimuat.</em>';

        try {
            const data = await ambilAlamat(lat, lng);
            const a = data.address || {};

            const baris = [
                ['Jalan', a.road],
                ['Kelurahan/Desa', a.suburb || a.neighbourhood || a.village],
                ['Kota/Kabupaten', a.city || a.town || a.municipality || a.county],
                ['Provinsi', a.state],
                ['Kode pos', a.postcode],
            ].map(([k, v]) => `
                <tr>
                    <td style="padding: 2px 8px 2px 0; color: #6b7280;">
                        ${k}
                    </td>
                    <td>${Peta.esc(v) || '-'}</td>
                </tr>
            `).join('');

            bagianAlamat = `
                <p style="margin: 6px 0;">
                    ${Peta.esc(data.display_name || '-')}
                </p>
                <table style="font-size: 13px;">
                    ${baris}
                </table>
            `;
        } catch (err) {
            console.error(err);
        }

        const daftar = dekat.map((d) => `
            <li data-npsn="${Peta.esc(d.fitur.properties.npsn)}"
                style="padding: 6px 0; border-top: 1px solid #e5e7eb; cursor: pointer;">
                <strong>${Peta.esc(d.fitur.properties.nama)}</strong><br>
                <small>${d.jarak.toFixed(2)} km (garis lurus)</small>
            </li>
        `).join('');

        panel.innerHTML = `
            <strong>Koordinat</strong>
            <div style="font-family: monospace;">
                ${lat.toFixed(6)}, ${lng.toFixed(6)}
            </div>

            <strong style="display: block; margin-top: 8px;">
                Alamat
            </strong>

            ${bagianAlamat}

            <strong style="display: block; margin-top: 8px;">
                Sekolah terdekat
            </strong>

            <ul style="list-style: none; margin: 4px 0 0; padding: 0;">
                ${daftar}
            </ul>
        `;
    });

    // Klik pada daftar sekolah terdekat
    panel.addEventListener('click', (e) => {
        const li = e.target.closest('[data-npsn]');

        if (!li) {
            return;
        }

        const f = semua.find(
            (x) => x.properties.npsn === li.dataset.npsn
        );

        if (f) {
            L.popup()
                .setLatLng(Peta.latlng(f))
                .setContent(Peta.popup(f))
                .openOn(map);

            map.flyTo(Peta.latlng(f), 17);
        }
    });

    // Tombol untuk mencari sekolah terdekat dari lokasi pengguna
    const tombolLokasi = document.getElementById('lokasi-saya');

    tombolLokasi.addEventListener('click', () => {

        if (!navigator.geolocation) {
            alert('Browser tidak mendukung fitur lokasi.');
            return;
        }

        if (semua.length === 0) {
            alert('Data sekolah belum tersedia. Silakan coba lagi.');
            return;
        }

        tombolLokasi.disabled = true;
        tombolLokasi.textContent = 'Mencari lokasi...';

        navigator.geolocation.getCurrentPosition(
            (posisi) => {

                tombolLokasi.disabled = false;
                tombolLokasi.textContent = 'Lokasi saya';

                const lat = posisi.coords.latitude;
                const lng = posisi.coords.longitude;
                const titik = [lat, lng];

                lapisan.clearLayers();

                // Marker lokasi pengguna
                L.circleMarker(titik, {
                    radius: 10,
                    color: '#ffffff',
                    weight: 3,
                    fillColor: '#16a34a',
                    fillOpacity: 1
                })
                .bindPopup('Lokasi saya')
                .addTo(lapisan)
                .openPopup();

                // Mencari tiga sekolah terdekat
                const dekat = terdekat(titik, 3);

                // Garis penghubung ke sekolah terdekat
                dekat.forEach((d) => {
                    L.polyline(
                        [titik, Peta.latlng(d.fitur)],
                        {
                            color: '#2563eb',
                            weight: 2,
                            dashArray: '6 6'
                        }
                    ).addTo(lapisan);
                });

                map.flyTo(titik, 14);

                // Membuat daftar sekolah terdekat
                const daftar = dekat.map((d) => `
                    <li data-npsn="${Peta.esc(d.fitur.properties.npsn)}"
                        style="padding: 6px 0;
                               border-top: 1px solid #e5e7eb;
                               cursor: pointer;">
                        <strong>${Peta.esc(d.fitur.properties.nama)}</strong>
                        <br>
                        <small>${d.jarak.toFixed(2)} km (garis lurus)</small>
                    </li>
                `).join('');

                panel.style.display = 'block';

                panel.innerHTML = `
                    <strong>Lokasi Saya</strong>

                    <div style="font-family: monospace;">
                        ${lat.toFixed(6)}, ${lng.toFixed(6)}
                    </div>

                    <strong style="display: block; margin-top: 10px;">
                        Tiga Sekolah Terdekat
                    </strong>

                    <ul style="list-style: none;
                               margin: 4px 0 0; padding: 0;">
                        ${daftar || '<li>Belum ada data sekolah.</li>'}
                    </ul>
                `;
            },

            (error) => {

                tombolLokasi.disabled = false;
                tombolLokasi.textContent = 'Lokasi saya';

                let pesan = 'Tidak dapat mengambil lokasi pengguna.';

                if (error.code === 1) {
                    pesan = 'Izin lokasi ditolak. Aktifkan izin lokasi pada browser.';
                } else if (error.code === 2) {
                    pesan = 'Lokasi tidak tersedia. Periksa GPS atau koneksi perangkat.';
                } else if (error.code === 3) {
                    pesan = 'Waktu pengambilan lokasi habis. Silakan coba lagi.';
                }

                alert(pesan);
            },

            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
    
</script>
@endpush
