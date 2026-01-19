<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HidroponikController extends Controller
{
    public function index()
    {
        return view('hidroponik');
    }

    public function detect(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10048',
        ]);

        // 1. Simpan Gambar
        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $image->move(public_path('uploads'), $imageName);
        $imagePath = public_path('uploads/' . $imageName);

        // Baca file gambar sebagai string (untuk dikirim ke Roboflow)
        $imageData = file_get_contents($imagePath);
        list($width, $height) = getimagesize($imagePath);

        // ==========================
        // A. REQUEST KE LOCAL PYTHON
        // ==========================
        $localDetections = [];
        try {
            $responseLocal = Http::attach(
                'file',
                fopen($imagePath, 'r'),
                $imageName
            )->post('http://127.0.0.1:5000/detect');

            if ($responseLocal->successful()) {
                $localDetections = $responseLocal->json()['detections'];
            }
        } catch (\Exception $e) {
            // Abaikan error local, biarkan kosong
        }

        // ==========================
        // B. REQUEST KE ROBOFLOW
        // ==========================
        $roboflowDetections = [];

        // --- ISI KREDENSIAL ROBOFLOW DISINI ---
        $apiKey = "kmx8hDI9sYpFmPiSoZJV";
        $modelId = "selada-dan-pakcoy-3tde5/4"; // Ganti dengan Model ID
        $confidence = 0.40;
        // --------------------------------------

        try {
            // PERBAIKAN DISINI: Menggunakan kutip dua (") di awal dan akhir
            $urlRoboflow = "https://detect.roboflow.com/{$modelId}?api_key={$apiKey}&confidence={$confidence}";

            // Roboflow menerima raw body image
            $responseRoboflow = Http::withBody($imageData, 'application/x-www-form-urlencoded')
                ->post($urlRoboflow);

            if ($responseRoboflow->successful()) {
                $rawRoboflow = $responseRoboflow->json()['predictions'];
                // Konversi format Roboflow agar SAMA dengan format Local
                $roboflowDetections = $this->normalizeRoboflowData($rawRoboflow);
            }
        } catch (\Exception $e) {
            // Abaikan error roboflow
        }

        // 3. Kirim Kedua Data ke View

        return view('hidroponik', [
            'result' => true,
            'image_url' => asset('uploads/' . $imageName),
            'image_width' => $width,
            'image_height' => $height,
            'detections_local' => $localDetections,      // Data 1
            'detections_roboflow' => $roboflowDetections // Data 2
        ]);
    }
    // FUNGSI BARU: Jembatan ke Chatbot Python
    public function chatPython(Request $request)
    {
        // 1. Validasi pesan tidak boleh kosong
        $request->validate([
            'message' => 'required|string',
        ]);

        try {
            // 2. Tembak ke Python (Port 5000) endpoint /chat
            $response = Http::post('http://127.0.0.1:5000/chat', [
                'message' => $request->input('message')
            ]);

            // 3. Cek apakah Python berhasil menjawab
            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['reply' => 'Maaf, Python tidak merespon.'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['reply' => 'Error Laravel: ' . $e->getMessage()], 500);
        }
    }

    private function normalizeRoboflowData($predictions)
    {
        $formatted = [];
        foreach ($predictions as $pred) {

            $x1 = $pred['x'] - ($pred['width'] / 2);
            $y1 = $pred['y'] - ($pred['height'] / 2);
            $x2 = $pred['x'] + ($pred['width'] / 2);
            $y2 = $pred['y'] + ($pred['height'] / 2);

            $formatted[] = [
                'name' => $pred['class'],
                'confidence' => $pred['confidence'],
                'box' => [
                    'x1' => $x1,
                    'y1' => $y1,
                    'x2' => $x2,
                    'y2' => $y2
                ]
            ];
        }
        return $formatted;
    }
}
