@php
    $teks = [
        'npsn'      => 'NPSN (8 digit)',
        'nama'      => 'Nama Sekolah',
        'kecamatan' => 'Kecamatan',
        'telepon'   => 'Telepon',
    ];

    $pilihan = [
        'jenjang' => ['SD', 'SMP', 'SMA', 'SMK'],
        'status'  => ['Negeri', 'Swasta'],
    ];

    $cls = 'w-full border rounded px-3 py-2';
@endphp

<div class="bg-white rounded shadow p-6">
    <div class="flex flex-col md:flex-row gap-6 items-start">

        {{-- KOLOM KIRI: DATA SEKOLAH --}}
        <div class="w-full md:w-1/2 min-w-0 space-y-4">

            @foreach ($teks as $name => $label)
                <div>
                    <label for="{{ $name }}" class="block font-medium mb-1">
                        {{ $label }}
                    </label>

                    <input type="text" id="{{ $name }}" name="{{ $name }}"
                        value="{{ old($name, $sekolah->$name) }}"
                        @readonly($name === 'npsn' && auth()->user()->isSekolah())
                        class="{{ $cls }}">

                    @error($name)
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            @foreach ($pilihan as $name => $opsi)
                <div>
                    <label for="{{ $name }}" class="block font-medium mb-1">
                        {{ ucfirst($name) }}
                    </label>

                    <select id="{{ $name }}" name="{{ $name }}" class="{{ $cls }}">
                        <option value="">-- Pilih --</option>

                        @foreach ($opsi as $o)
                            <option
                                value="{{ $o }}"
                                @selected(old($name, $sekolah->$name) === $o)
                            >
                                {{ $o }}
                            </option>
                        @endforeach
                    </select>

                    @error($name)
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div>
                <label for="alamat" class="block font-medium mb-1">
                    Alamat
                </label>

                <textarea
                    id="alamat"
                    name="alamat"
                    rows="4"
                    class="{{ $cls }}"
                >{{ old('alamat', $sekolah->alamat) }}</textarea>

                @error('alamat')
                    <p class="text-red-600 text-sm">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- KOLOM KANAN: PETA DAN KOORDINAT --}}
        <div class="w-full md:w-1/2 min-w-0 space-y-4">

            <p class="font-medium">
                Pilih lokasi sekolah
            </p>

            <div
                id="map-picker"
                class="w-full rounded-lg border border-gray-300 shadow-sm"
                style="height: 400px; min-height: 400px;"
            ></div>

            <p class="text-sm text-gray-500">
                Klik peta atau geser marker untuk menentukan lokasi sekolah.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="latitude" class="block font-medium mb-1">
                        Latitude
                    </label>

                    <input
                        type="number"
                        step="any"
                        id="latitude"
                        name="latitude"
                        value="{{ old('latitude', $sekolah->latitude) }}"
                        class="{{ $cls }}"
                    >

                    @error('latitude')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="longitude" class="block font-medium mb-1">
                        Longitude
                    </label>

                    <input
                        type="number"
                        step="any"
                        id="longitude"
                        name="longitude"
                        value="{{ old('longitude', $sekolah->longitude) }}"
                        class="{{ $cls }}"
                    >

                    @error('longitude')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button
                    type="submit"
                    class="px-5 py-2 bg-blue-700 text-white rounded hover:bg-blue-800"
                >
                    Simpan
                </button>

                <a  href="{{ auth()->user()->isDinas()
                            ? route('dinas.sekolah.index')
                            : route('dashboard') }}"
                    class="px-5 py-2 bg-gray-300 rounded">Batal</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const mapElement = document.getElementById('map-picker');

    if (!mapElement || typeof L === 'undefined') {
        console.error('Leaflet atau elemen peta tidak ditemukan.');
        return;
    }

    const lat = parseFloat(latInput.value);
    const lng = parseFloat(lngInput.value);

    const valid =
        latInput.value.trim() !== '' &&
        lngInput.value.trim() !== '' &&
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        lat >= -90 && lat <= 90 &&
        lng >= -180 && lng <= 180;

    const pusat = valid ? [lat, lng] : [-3.3194, 114.5908];

    const map = L.map('map-picker').setView(pusat, valid ? 16 : 13);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker(pusat, {
        draggable: true
    }).addTo(map);

    function updateKoordinat(lat, lng) {
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
    }

    map.on('click', function (e) {
        marker.setLatLng(e.latlng);
        updateKoordinat(e.latlng.lat, e.latlng.lng);
    });

    marker.on('dragend', function () {
        const posisi = marker.getLatLng();
        updateKoordinat(posisi.lat, posisi.lng);
    });

    function updateMarker() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);

        if (
            latInput.value.trim() !== '' &&
            lngInput.value.trim() !== '' &&
            Number.isFinite(lat) &&
            Number.isFinite(lng) &&
            lat >= -90 && lat <= 90 &&
            lng >= -180 && lng <= 180
        ) {
            marker.setLatLng([lat, lng]);
            map.setView([lat, lng], 16);
        }
    }

    latInput.addEventListener('change', updateMarker);
    lngInput.addEventListener('change', updateMarker);

    if (!valid) {
        updateKoordinat(pusat[0], pusat[1]);
    }

    setTimeout(function () {
        map.invalidateSize();
    }, 300);
});
</script>
@endpush
