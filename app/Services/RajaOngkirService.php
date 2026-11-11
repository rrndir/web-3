<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RajaOngkirService
{
    public function isConfigured(): bool
    {
        return $this->canSearch()
            && filled(config('shipping.rajaongkir.origin_city_id'));
    }

    public function canSearch(): bool
    {
        return filled(config('shipping.rajaongkir.api_key'))
            && filled(config('shipping.rajaongkir.base_url'));
    }

    public function destinations(string $search): array
    {
        $payload = $this->get('/destination/domestic-destination', [
            'search' => $search,
            'limit' => 20,
            'offset' => 0,
        ])->json();

        $destinations = $this->data($payload, 'destinations');

        return array_values(array_filter(array_map(function ($destination) {
            if (!is_array($destination) || !isset($destination['id'])) {
                return null;
            }

            return [
                'id' => (string) $destination['id'],
                'label' => (string) ($destination['label'] ?? ''),
                'province_name' => (string) ($destination['province_name'] ?? ''),
                'city_name' => (string) ($destination['city_name'] ?? ''),
                'district_name' => (string) ($destination['district_name'] ?? ''),
                'subdistrict_name' => (string) ($destination['subdistrict_name'] ?? ''),
                'zip_code' => (string) ($destination['zip_code'] ?? ''),
            ];
        }, $destinations), fn ($destination) => $destination !== null && $destination['label'] !== ''));
    }

    public function destination(string $destinationId, string $search): ?array
    {
        foreach ($this->destinations($search) as $destination) {
            if ($destination['id'] === $destinationId) {
                return $destination;
            }
        }

        return null;
    }

    public function quotes(string $destinationCityId, int $weight, string $courier): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException(
                'RajaOngkir Komerce belum dikonfigurasi. Admin perlu mengatur API key dan ID lokasi asal pengiriman.'
            );
        }

        if (!isset(config('shipping.rajaongkir.couriers')[$courier])) {
            throw new RuntimeException('Kurir yang dipilih tidak tersedia.');
        }

        $payload = $this->post('/calculate/domestic-cost', [
            'origin' => config('shipping.rajaongkir.origin_city_id'),
            'destination' => $destinationCityId,
            'weight' => max(1, $weight),
            'courier' => $courier,
            'price' => 'lowest',
        ])->json();

        $rates = $this->data($payload, 'shipping rates');
        $quotes = [];
        foreach ($rates as $rate) {
            if (!is_array($rate) || !is_numeric($rate['cost'] ?? null)) {
                continue;
            }

            $quotes[] = [
                'service' => (string) ($rate['service'] ?? ''),
                'description' => (string) ($rate['description'] ?? $rate['name'] ?? ''),
                'cost' => (int) $rate['cost'],
                'etd' => (string) ($rate['etd'] ?? '') ?: 'Tidak tersedia',
            ];
        }

        if ($quotes === []) {
            throw new RuntimeException('RajaOngkir tidak mengembalikan layanan untuk kurir dan tujuan ini.');
        }

        return $quotes;
    }

    private function get(string $path, array $query = [])
    {
        try {
            return $this->request()->get($this->url($path), $query)->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->httpErrorMessage($exception, 'Tidak dapat mengambil data'),
                0,
                $exception
            );
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke RajaOngkir. Periksa koneksi internet, DNS, firewall, dan endpoint API.',
                0,
                $exception
            );
        }
    }

    private function post(string $path, array $data)
    {
        try {
            return $this->request()->asForm()->post($this->url($path), $data)->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->httpErrorMessage($exception, 'RajaOngkir gagal menghitung ongkir'),
                0,
                $exception
            );
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke RajaOngkir. Periksa koneksi internet, DNS, firewall, dan endpoint API.',
                0,
                $exception
            );
        }
    }

    private function request()
    {
        if (!$this->canSearch()) {
            throw new RuntimeException(
                'RajaOngkir Komerce belum dikonfigurasi. Admin perlu mengatur API key dan endpoint.'
            );
        }

        return Http::acceptJson()
            ->withHeaders(['key' => config('shipping.rajaongkir.api_key')])
            ->timeout(15);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('shipping.rajaongkir.base_url'), '/') . $path;
    }

    private function data($payload, string $resource): array
    {
        if (!is_array($payload)) {
            throw new RuntimeException("Format respons RajaOngkir untuk {$resource} tidak dikenali.");
        }

        $meta = $payload['meta'] ?? [];
        if (isset($meta['code']) && (int) $meta['code'] !== 200) {
            throw new RuntimeException((string) ($meta['message'] ?? "RajaOngkir gagal mengambil {$resource}."));
        }

        $data = $payload['data'] ?? null;
        if (!is_array($data)) {
            throw new RuntimeException("Format respons RajaOngkir untuk {$resource} tidak dikenali.");
        }

        return $data;
    }

    private function httpErrorMessage(RequestException $exception, string $action): string
    {
        $status = $exception->response->status();
        $description = $exception->response->json('meta.message')
            ?? $exception->response->json('message');
        $hint = match ($status) {
            401, 403 => 'Periksa API key dan akses paket API Komerce.',
            404 => 'Endpoint API Komerce tidak ditemukan. Pastikan base URL berakhiran /api/v1.',
            429 => 'Batas permintaan API tercapai. Tunggu sebentar lalu coba lagi.',
            default => 'Periksa status layanan RajaOngkir dan konfigurasi akun.',
        };
        $providerMessage = is_string($description) && $description !== ''
            ? " Pesan provider: {$description}"
            : '';

        return "{$action} (HTTP {$status}). {$hint}{$providerMessage}";
    }
}
