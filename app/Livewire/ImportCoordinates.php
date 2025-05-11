<?php

namespace App\Livewire;

use App\Models\Coordinate;
use App\Services\CoordinateConversionService;
use Livewire\Component;
use Livewire\WithFileUploads;
use League\Csv\Reader;
use League\Csv\Writer;

class ImportCoordinates extends Component
{
    use WithFileUploads;

    public $file;
    public $format = 'utm';
    public $datum = 'SIRGAS2000';
    public $utmZone;
    public $centralMeridian;
    public $showModal = false;

    protected $rules = [
        'file' => 'required|file|mimes:csv,txt|max:10240',
        'format' => 'required|in:utm,decimal,gms',
        'datum' => 'required|in:SIRGAS2000,SAD69',
        'utmZone' => 'required_if:format,utm|integer|between:18,25',
        'centralMeridian' => 'required_if:format,utm|string',
    ];

    protected $conversionService;

    public function mount()
    {
        $this->utmZone = 24; // Default para Brasil
        $this->centralMeridian = '24°S';
        $this->conversionService = new CoordinateConversionService();
    }

    public function import()
    {
        $this->validate();

        if (!session()->has('current_service_id')) {
            $this->addError('service', 'Nenhum serviço selecionado.');
            return;
        }

        $csv = Reader::createFromPath($this->file->getRealPath());
        $csv->setDelimiter($this->detectDelimiter($this->file->getRealPath()));
        $records = $csv->getRecords();

        foreach ($records as $record) {
            $this->processRecord($record);
        }

        $this->showModal = false;
        $this->reset(['file']);
        $this->dispatch('coordinates-imported');
    }

    protected function processRecord($record)
    {
        $data = [
            'service_id' => session()->get('current_service_id'),
            'datum' => $this->datum,
            'utm_zone' => $this->utmZone,
            'central_meridian' => $this->centralMeridian,
        ];

        switch ($this->format) {
            case 'utm':
                $data['point'] = $record[0];
                $data['description'] = $record[1];
                $data['utm_north'] = $record[2];
                $data['utm_east'] = $record[3];
                $data['elevation'] = $record[4];
                $this->convertFromUTM($data);
                break;

            case 'decimal':
                $data['point'] = $record[0];
                $data['description'] = $record[1];
                $data['latitude_decimal'] = $record[2];
                $data['longitude_decimal'] = $record[3];
                $data['elevation'] = $record[4];
                $this->convertFromDecimal($data);
                break;

            case 'gms':
                $data['point'] = $record[0];
                $data['description'] = $record[1];
                $data['latitude_gms'] = $record[2];
                $data['longitude_gms'] = $record[3];
                $data['elevation'] = $record[4];
                $this->convertFromGMS($data);
                break;
        }

        Coordinate::create($data);
    }

    protected function convertFromUTM(&$data)
    {
        $decimal = $this->conversionService->utmToDecimal(
            $data['utm_north'],
            $data['utm_east'],
            $this->utmZone,
            $this->datum
        );

        $data['latitude_decimal'] = $decimal['latitude_decimal'];
        $data['longitude_decimal'] = $decimal['longitude_decimal'];

        $gms = $this->conversionService->decimalToGMS(
            $decimal['latitude_decimal'],
            $decimal['longitude_decimal']
        );

        $data['latitude_gms'] = $gms['latitude_gms'];
        $data['longitude_gms'] = $gms['longitude_gms'];
    }

    protected function convertFromDecimal(&$data)
    {
        $utm = $this->conversionService->decimalToUTM(
            $data['latitude_decimal'],
            $data['longitude_decimal'],
            $this->utmZone,
            $this->datum
        );

        $data['utm_north'] = $utm['utm_north'];
        $data['utm_east'] = $utm['utm_east'];

        $gms = $this->conversionService->decimalToGMS(
            $data['latitude_decimal'],
            $data['longitude_decimal']
        );

        $data['latitude_gms'] = $gms['latitude_gms'];
        $data['longitude_gms'] = $gms['longitude_gms'];
    }

    protected function convertFromGMS(&$data)
    {
        $decimal = $this->conversionService->gmsToDecimal(
            $data['latitude_gms'],
            $data['longitude_gms']
        );

        $data['latitude_decimal'] = $decimal['latitude_decimal'];
        $data['longitude_decimal'] = $decimal['longitude_decimal'];

        $utm = $this->conversionService->decimalToUTM(
            $decimal['latitude_decimal'],
            $decimal['longitude_decimal'],
            $this->utmZone,
            $this->datum
        );

        $data['utm_north'] = $utm['utm_north'];
        $data['utm_east'] = $utm['utm_east'];
    }

    protected function detectDelimiter($file)
    {
        $content = file_get_contents($file);
        $delimiters = [',', ';', "\t"];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($content, $delimiter);
        }

        return array_search(max($counts), $counts);
    }

    public function downloadTemplate()
    {
        $csv = Writer::createFromString('');
        
        switch ($this->format) {
            case 'utm':
                $csv->insertOne(['ponto', 'descricao', 'norte', 'leste', 'cota']);
                break;
            case 'decimal':
                $csv->insertOne(['ponto', 'descricao', 'latitude_decimal', 'longitude_decimal', 'cota']);
                break;
            case 'gms':
                $csv->insertOne(['ponto', 'descricao', 'latitude_GMS', 'longitude_GMS', 'cota']);
                break;
        }

        return response()->streamDownload(function () use ($csv) {
            echo $csv->toString();
        }, 'template_coordenadas.csv');
    }

    public function render()
    {
        return view('livewire.import-coordinates');
    }
} 