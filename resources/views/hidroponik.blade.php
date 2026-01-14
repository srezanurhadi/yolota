<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Hidroponik AI Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Transisi halus untuk perpindahan tab */
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-800 p-4 md:p-8">

    <div class="max-w-7xl mx-auto">

        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-emerald-700 mb-2">🌱 Smart Hidroponik Monitor</h1>
            <p class="text-gray-500">Dual Engine Analysis: Local Python & Roboflow Cloud</p>
        </div>

        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded shadow-sm">
                <p class="font-bold flex items-center gap-2">⚠️ Terjadi Kesalahan</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700 flex items-center gap-2">
                        📸 Upload Gambar Tanaman
                    </h2>

                    <form action="/detect" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div
                            class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:bg-gray-50 transition bg-gray-50/50 group">
                            <input type="file" name="image" id="imageInput" accept="image/*" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-100 file:text-emerald-700 hover:file:bg-emerald-200 cursor-pointer"
                                onchange="previewImage()">

                            <div id="previewBox" class="hidden mt-4">
                                <p class="text-xs text-gray-400 mb-2">Preview Gambar:</p>
                                <img id="imgPreview" src=""
                                    class="max-h-64 mx-auto rounded-lg shadow-sm border">
                            </div>
                        </div>

                        <button type="submit"
                            class="mt-4 w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition shadow-md flex justify-center items-center gap-2">
                            🔍 Mulai Analisis
                        </button>
                    </form>
                </div>

                @if (isset($result))
                    <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">

                        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                            <h2 class="text-xl font-bold text-gray-800">✅ Hasil Analisis</h2>

                            <div class="flex bg-gray-100 p-1 rounded-lg">
                                <button onclick="switchMode('local')" id="btnLocal"
                                    class="px-4 py-2 rounded-md text-sm font-bold bg-white shadow text-emerald-700 transition flex items-center gap-2">
                                    🖥️ Local Python
                                </button>
                                <button onclick="switchMode('roboflow')" id="btnRoboflow"
                                    class="px-4 py-2 rounded-md text-sm font-bold text-gray-500 hover:bg-white hover:shadow transition flex items-center gap-2">
                                    ☁️ Roboflow
                                </button>
                            </div>
                        </div>

                        <div
                            class="relative w-full rounded-lg overflow-hidden shadow-lg border border-gray-200 bg-gray-900 group">
                            <img id="resultImage" src="{{ $image_url }}" class="w-full h-auto block opacity-90"
                                alt="Hasil Deteksi" crossorigin="anonymous">

                            <div id="layer-local" class="fade-in">
                                @foreach ($detections_local as $det)
                                    @php
                                        // Rumus Persentase CSS
                                        $x = ($det['box']['x1'] / $image_width) * 100;
                                        $y = ($det['box']['y1'] / $image_height) * 100;
                                        $w = (($det['box']['x2'] - $det['box']['x1']) / $image_width) * 100;
                                        $h = (($det['box']['y2'] - $det['box']['y1']) / $image_height) * 100;

                                        // Logika Warna Local (Hijau vs Merah)
                                        $isLayu = str_contains(strtolower($det['name']), 'layu');
                                        $borderColor = $isLayu ? 'border-red-500' : 'border-emerald-400';
                                        $bgColor = $isLayu ? 'bg-red-600' : 'bg-emerald-600';
                                    @endphp
                                    <div class="absolute border-2 {{ $borderColor }} hover:bg-white/10 transition-colors cursor-crosshair"
                                        style="left: {{ $x }}%; top: {{ $y }}%; width: {{ $w }}%; height: {{ $h }}%;">
                                        <span
                                            class="absolute top-0 left-0 {{ $bgColor }} text-white text-[10px] md:text-xs font-bold px-1.5 py-0.5 shadow-sm opacity-90 truncate max-w-full">
                                            {{ $det['name'] }} {{ round($det['confidence'] * 100) }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            <div id="layer-roboflow" class="hidden fade-in">
                                @foreach ($detections_roboflow as $det)
                                    @php
                                        $x = ($det['box']['x1'] / $image_width) * 100;
                                        $y = ($det['box']['y1'] / $image_height) * 100;
                                        $w = (($det['box']['x2'] - $det['box']['x1']) / $image_width) * 100;
                                        $h = (($det['box']['y2'] - $det['box']['y1']) / $image_height) * 100;

                                        // Logika Warna Roboflow (Biru vs Orange - Biar Beda)
                                        $isLayu = str_contains(strtolower($det['name']), 'layu');
                                        $borderColor = $isLayu ? 'border-orange-500' : 'border-blue-400';
                                        $bgColor = $isLayu ? 'bg-orange-600' : 'bg-blue-600';
                                    @endphp
                                    <div class="absolute border-2 {{ $borderColor }} hover:bg-white/10 transition-colors cursor-crosshair"
                                        style="left: {{ $x }}%; top: {{ $y }}%; width: {{ $w }}%; height: {{ $h }}%;">
                                        <span
                                            class="absolute top-0 left-0 {{ $bgColor }} text-white text-[10px] md:text-xs font-bold px-1.5 py-0.5 shadow-sm opacity-90 truncate max-w-full">
                                            RF: {{ $det['name'] }} {{ round($det['confidence'] * 100) }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-4 justify-between items-center">
                            <div class="text-xs text-gray-500 font-mono bg-gray-100 px-3 py-2 rounded">
                                <span id="count-info">Local: <strong>{{ count($detections_local) }}</strong> objek
                                    ditemukan.</span>
                            </div>

                            <div class="flex gap-2">
                                <button onclick="downloadResult()"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2 px-4 rounded shadow transition flex items-center gap-2">
                                    📥 Unduh Gambar Hasil
                                </button>
                                <button onclick="document.getElementById('jsonOutput').classList.toggle('hidden')"
                                    class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-bold py-2 px-4 rounded shadow transition">
                                    {} JSON
                                </button>
                            </div>
                        </div>

                        <div id="jsonOutput"
                            class="hidden mt-4 p-4 bg-gray-900 text-green-400 text-xs rounded-lg overflow-x-auto h-48 font-mono border border-gray-700 shadow-inner">
                            <div class="mb-2 text-gray-500 border-b border-gray-700 pb-1">RAW DATA (Local & Roboflow)
                            </div>
                            <pre>Local Data: @json($detections_local, JSON_PRETTY_PRINT)</pre>
                            <hr class="border-gray-700 my-4">
                            <pre>Roboflow Data: @json($detections_roboflow, JSON_PRETTY_PRINT)</pre>
                        </div>

                    </div>
                @endif
            </div>

            <div class="lg:col-span-1">
                <div
                    class="bg-white rounded-xl shadow-md flex flex-col h-full min-h-[500px] border border-gray-100 sticky top-6">
                    <div class="p-4 border-b bg-gray-50 rounded-t-xl">
                        <h2 class="font-semibold text-gray-700 flex items-center gap-2">
                            🤖 Asisten AI
                        </h2>
                        <p class="text-xs text-gray-400">Tanyakan solusi perawatan...</p>
                    </div>

                    <div
                        class="flex-1 p-4 bg-gray-50/50 flex flex-col items-center justify-center text-center text-gray-400 space-y-3">
                        <div class="text-4xl">💬</div>
                        <p class="text-sm italic">Fitur Chatbot LLM akan segera hadir.</p>
                        <p class="text-xs">Nanti bisa otomatis membaca hasil deteksi di samping.</p>
                    </div>

                    <div class="p-4 border-t bg-white rounded-b-xl">
                        <div class="flex gap-2">
                            <input type="text" placeholder="Ketik pertanyaan..." disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm focus:outline-none cursor-not-allowed">
                            <button disabled class="bg-emerald-600 opacity-50 text-white p-2 rounded-lg">
                                ➤
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Data dari PHP disimpan ke JS Variable untuk kebutuhan Download & Switcher
        // Tanda ?? [] artinya kalau null diganti array kosong biar gak error
        const dataLocal = @json($detections_local ?? []);
        const dataRoboflow = @json($detections_roboflow ?? []);

        let currentMode = 'local'; // Default mode

        // 1. PREVIEW GAMBAR
        function previewImage() {
            const input = document.getElementById('imageInput');
            const previewBox = document.getElementById('previewBox');
            const imgPreview = document.getElementById('imgPreview');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                    previewBox.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 2. SWITCH TAB (LOCAL <-> ROBOFLOW)
        function switchMode(mode) {
            currentMode = mode;
            const layerLocal = document.getElementById('layer-local');
            const layerRoboflow = document.getElementById('layer-roboflow');
            const btnLocal = document.getElementById('btnLocal');
            const btnRoboflow = document.getElementById('btnRoboflow');
            const countInfo = document.getElementById('count-info');

            if (mode === 'local') {
                // Tampilkan Local
                layerLocal.classList.remove('hidden');
                layerRoboflow.classList.add('hidden');

                // Style Tombol
                btnLocal.classList.add('bg-white', 'shadow', 'text-emerald-700');
                btnLocal.classList.remove('text-gray-500', 'hover:bg-white');

                btnRoboflow.classList.remove('bg-white', 'shadow', 'text-emerald-700');
                btnRoboflow.classList.add('text-gray-500', 'hover:bg-white');

                countInfo.innerHTML = `Local: <strong>${dataLocal.length}</strong> objek ditemukan.`;
            } else {
                // Tampilkan Roboflow
                layerLocal.classList.add('hidden');
                layerRoboflow.classList.remove('hidden');

                // Style Tombol
                btnRoboflow.classList.add('bg-white', 'shadow', 'text-emerald-700');
                btnRoboflow.classList.remove('text-gray-500', 'hover:bg-white');

                btnLocal.classList.remove('bg-white', 'shadow', 'text-emerald-700');
                btnLocal.classList.add('text-gray-500', 'hover:bg-white');

                countInfo.innerHTML = `Roboflow: <strong>${dataRoboflow.length}</strong> objek ditemukan.`;
            }
        }

        // 3. DOWNLOAD HASIL (CANVAS DRAWING)
        function downloadResult() {
            // Tentukan data mana yang mau di-download berdasarkan tab aktif
            const activeData = (currentMode === 'local') ? dataLocal : dataRoboflow;

            const imgElement = document.getElementById('resultImage');
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Set ukuran canvas SAMA dengan ukuran ASLI gambar
            canvas.width = imgElement.naturalWidth;
            canvas.height = imgElement.naturalHeight;

            // 1. Gambar Foto Asli
            ctx.drawImage(imgElement, 0, 0, canvas.width, canvas.height);

            // 2. Gambar Kotak & Text
            activeData.forEach(det => {
                const box = det.box;
                const name = det.name;
                const conf = Math.round(det.confidence * 100) + '%';
                const label = (currentMode === 'local' ? '' : 'RF: ') + `${name} ${conf}`;

                // Tentukan Warna
                const isLayu = name.toLowerCase().includes('layu');
                let color;

                if (currentMode === 'local') {
                    color = isLayu ? '#ef4444' : '#10b981'; // Merah / Emerald
                } else {
                    color = isLayu ? '#f97316' : '#3b82f6'; // Orange / Biru
                }

                // Gambar Kotak
                ctx.strokeStyle = color;
                ctx.lineWidth = 4;
                ctx.strokeRect(box.x1, box.y1, (box.x2 - box.x1), (box.y2 - box.y1));

                // Gambar Background Label (DI DALAM KOTAK)
                ctx.font = 'bold 24px Arial';
                const textWidth = ctx.measureText(label).width;
                const textHeight = 34;

                ctx.fillStyle = color;
                // Gambar kotak background text di pojok kiri atas (di dalam)
                ctx.fillRect(box.x1, box.y1, textWidth + 12, textHeight);

                // Gambar Teksnya
                ctx.fillStyle = 'white';
                ctx.fillText(label, box.x1 + 6, box.y1 + 24);
            });

            // Trigger Download
            const link = document.createElement('a');
            link.download = `hasil-${currentMode}-hidroponik.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    </script>
</body>

</html>
