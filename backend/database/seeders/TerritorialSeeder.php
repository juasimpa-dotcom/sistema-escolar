<?php

namespace Database\Seeders;

use App\Enums\TipoMacrodistrito;
use App\Models\DistritoMunicipal;
use App\Models\Macrodistrito;
use App\Models\Municipio;
use Illuminate\Database\Seeder;

/**
 * Jerarquía territorial del municipio de La Paz.
 *
 * Los macrodistritos son datos oficiales fijos. Los 24 distritos municipales
 * se leen de database/data/distritos.csv (columnas: numero, macrodistrito_codigo)
 * para no inventar a qué macrodistrito pertenece cada uno.
 */
class TerritorialSeeder extends Seeder
{
    private const MACRODISTRITOS = [
        ['codigo' => 'MD1', 'nombre' => 'Cotahuma', 'tipo' => TipoMacrodistrito::URBANO],
        ['codigo' => 'MD2', 'nombre' => 'Max Paredes', 'tipo' => TipoMacrodistrito::URBANO],
        ['codigo' => 'MD3', 'nombre' => 'Periférica', 'tipo' => TipoMacrodistrito::URBANO],
        ['codigo' => 'MD4', 'nombre' => 'San Antonio', 'tipo' => TipoMacrodistrito::URBANO],
        ['codigo' => 'MD5', 'nombre' => 'Sur', 'tipo' => TipoMacrodistrito::URBANO],
        ['codigo' => 'MD6', 'nombre' => 'Mallasa', 'tipo' => TipoMacrodistrito::URBANO],
        ['codigo' => 'MD7', 'nombre' => 'Centro', 'tipo' => TipoMacrodistrito::URBANO],
        ['codigo' => 'MD8', 'nombre' => 'Zongo', 'tipo' => TipoMacrodistrito::RURAL],
        ['codigo' => 'MD9', 'nombre' => 'Hampaturi', 'tipo' => TipoMacrodistrito::RURAL],
    ];

    public function run(): void
    {
        $municipio = Municipio::updateOrCreate(['nombre' => 'La Paz'], []);

        foreach (self::MACRODISTRITOS as $macrodistrito) {
            Macrodistrito::updateOrCreate(
                ['codigo' => $macrodistrito['codigo']],
                [
                    'municipio_id' => $municipio->id,
                    'nombre' => $macrodistrito['nombre'],
                    'tipo' => $macrodistrito['tipo']->value,
                ]
            );
        }

        $this->sembrarDistritos();
    }

    private function sembrarDistritos(): void
    {
        $ruta = database_path('data/distritos.csv');

        if (! is_file($ruta)) {
            $this->command?->warn('No existe database/data/distritos.csv: se omiten los distritos municipales.');

            return;
        }

        $filas = $this->leerCsv($ruta);

        if ($filas === []) {
            $this->command?->warn('database/data/distritos.csv está vacío: se omiten los distritos municipales.');

            return;
        }

        $columnas = array_map('mb_strtolower', array_keys($filas[0]));

        if (! in_array('numero', $columnas, true)) {
            $this->command?->warn('database/data/distritos.csv no tiene columna "numero": se omiten los distritos municipales.');

            return;
        }

        $indiceNumero = array_search('numero', $columnas, true);
        $indiceMacro = array_search('macrodistrito_codigo', $columnas, true);
        $indiceNombre = array_search('nombre', $columnas, true);

        foreach ($filas as $fila) {
            $valores = array_values($fila);
            $numero = (int) trim((string) ($valores[$indiceNumero] ?? ''));

            if ($numero < 1) {
                continue;
            }

            $codigo = trim((string) ($indiceMacro !== false ? ($valores[$indiceMacro] ?? '') : ''));
            $macrodistrito = null;

            if ($codigo !== '') {
                $macrodistrito = Macrodistrito::where('codigo', $codigo)->first();

                if (! $macrodistrito) {
                    $this->command?->warn("Distrito {$numero}: macrodistrito \"{$codigo}\" no existe; se guarda sin asignar.");
                }
            }

            $nombre = $indiceNombre !== false ? trim((string) ($valores[$indiceNombre] ?? '')) : '';

            DistritoMunicipal::updateOrCreate(
                ['numero' => $numero],
                [
                    'macrodistrito_id' => $macrodistrito?->id,
                    'nombre' => $nombre !== '' ? $nombre : 'Distrito '.$numero,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function leerCsv(string $ruta): array
    {
        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            return [];
        }

        $lineas = explode("\n", str_replace("\r\n", "\n", $contenido));
        $filas = [];
        $cabecera = null;

        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $valores = str_getcsv($linea);

            if ($cabecera === null) {
                $primera = mb_strtolower(trim((string) $valores[0]));

                if ($primera === 'numero') {
                    $cabecera = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $valores);

                    continue;
                }

                $cabecera = ['numero', 'macrodistrito_codigo'];
            }

            $valores = array_map(fn ($v) => (string) $v, $valores);
            $valores = array_slice(array_pad($valores, count($cabecera), ''), 0, count($cabecera));
            $filas[] = array_combine($cabecera, $valores) ?: [];
        }

        return array_values(array_filter($filas));
    }
}
