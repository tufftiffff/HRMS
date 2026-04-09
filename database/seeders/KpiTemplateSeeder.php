<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KpiTemplate;

class KpiTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $kpis = [
            ['kpi_title' => 'Sales Target Q1', 'kpi_description' => 'Achieve RM50k in sales', 'kpi_type' => 'Quantitative', 'weight' => 40],
            ['kpi_title' => 'Code Quality', 'kpi_description' => 'Maintain <5 bugs per release', 'kpi_type' => 'Qualitative', 'weight' => 30],
            ['kpi_title' => 'Attendance Score', 'kpi_description' => '100% On-time arrival', 'kpi_type' => 'Quantitative', 'weight' => 20],
            ['kpi_title' => 'Team Collaboration', 'kpi_description' => 'Peer review feedback', 'kpi_type' => 'Qualitative', 'weight' => 10],
        ];

        foreach ($kpis as $kpi) {
            KpiTemplate::create($kpi);
        }
    }
}