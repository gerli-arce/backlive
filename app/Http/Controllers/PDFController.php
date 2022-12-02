<?php

namespace App\Http\Controllers;

use App\gLibraries\gTrace;
use App\gLibraries\gValidate;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Evidence;
use App\Models\Invoice;
use App\Models\Response;
use App\Models\View_activities;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class PDFController extends Controller
{
    static private function render(array $modules): array
    {
        $url = $_ENV['FILES_URL'];
        $service = $_ENV['FILES_SERVICE'];
        $token = $_ENV['FILES_TOKEN'];

        $summary = '';
        $implementation = '';
        $totalhours = 0;
        foreach ($modules as $m_key => $module) {
            $m_id = $m_key + 1;
            $s_activities = '';
            $i_activities = '';
            foreach ($module['activities'] as $a_key => $activity) {
                $a_id = $a_key + 1;
                $s_activities .= "{$m_id}.{$a_id}. {$activity['description']} <span class='fs-small text-grey'>({$activity['hours']})</span><br>";
                $evidences = '';
                foreach ($activity['evidences'] as $evidence) {
                    $file = $evidence['file'];
                    $evidences .= "<br><img class='evidence' src='{$url}/api/files/{$file}/{$service}/{$token}'><br><br>";
                }
                $i_activities .= "
                    {$m_id}.{$a_id}. {$activity['description']}<br>
                    {$evidences}
                ";
            }
            $totalhours += $module['hours'];
            $summary .= "
            <tr>
                <td class='text-center'>{$m_id}</td>
                <td>{$module['module']}</td>
                <td>{$s_activities}</td>
                <td class='text-center'>{$module['hours']}</td>
            </tr>
            ";
            $implementation .= "
            <b>{$module['module']}</b><br>
            {$i_activities}<br>
            ";
        }
        return [$summary, $totalhours, $implementation];
    }

    static private function setFromTo(string $from, string $to): string
    {
        [$year_from, $month_from] = explode('-', $from);
        [$year_to, $month_to] = explode('-', $to);

        $month_from = gTrace::month($month_from);
        $month_to = gTrace::month($month_to);

        if ($year_from != $year_to) {
            return "los meses de <b>{$month_from} {$year_from}</b> y <b>{$month_to} {$year_to}</b>";
        } else {
            if ($month_from != $month_to) {
                return "los meses de <b>{$month_from}</b> y <b>{$month_to}</b> del año <b>{$year_from}</b>";
            } else {
                return "el mes de <b>{$month_to}</b> del año <b>{$year_from}</b>";
            }
        }
    }
    
    public function generatePDF(Request $request)
    {
        try {

            [$status, $message, $role] = gValidate::get($request);

            if ($status != 200) {
                throw new Exception($message);
            }

            if (!gValidate::check($role->permissions, 'issues', 'generate_pdf')) {
                throw new Exception('No tiene permisos para generar Informes de PDF dentro del sistema');
            }

            if (!isset($request->id)) {
                throw new Exception('Envíe el identificador del informe a generar');
            }

            $invoice = Invoice::find($request->id);

            if (!$invoice) {
                throw new Exception('El informe que desea generar en PDF no existe');
            }

            $template = file_get_contents('../storage/templates/invoice.html');

            $modulesJpa = View_activities::select([
                'module__id AS id',
                'module__module AS module',
                DB::raw('SUM(hours__accepted) AS hours')
            ])
                ->groupBy('module')
                ->where('invoice__id', $request->id)
                ->where('status', 'ACEPTADA')
                ->get();

            foreach ($modulesJpa as $m_key => $module) {
                $activitiesJpa = View_activities::select([
                    'id',
                    'activity AS description',
                    'evidences',
                    'hours__accepted AS hours'
                ])
                    ->where('module__id', $module['id'])
                    ->where('invoice__id', $request->id)
                    ->where('status', 'ACEPTADA')
                    ->get();
                foreach ($activitiesJpa as $a_key => $activity) {
                    $evidences = array();
                    if ($activity['evidences'] > 0) {
                        $evidences = Evidence::select([
                            'file'
                        ])
                            ->where('_activity', $activity['id'])
                            ->get();
                    }
                    $activitiesJpa[$a_key]['evidences'] = $evidences;
                }
                $modulesJpa[$m_key]['activities'] = $activitiesJpa;
            }

            [$summary, $hours, $implementation] = PDFController::render($modulesJpa->toArray());

            $template = str_replace(
                [
                    '{issue_date}',
                    '{issue_id}',
                    '{issue_long_date}',
                    '{from_to_format}',
                    '{summary}',
                    '{total_hours}',
                    '{implementation}'
                ],
                [
                    $invoice->invoice_date,
                    $invoice->invoice_number,
                    gTrace::getDate('long'),
                    PDFController::setFromTo($invoice->month_from, $invoice->month_to),
                    $summary,
                    $hours,
                    $implementation
                ],
                $template
            );

            $pdf = App::make('dompdf.wrapper');

            $pdf->loadHTML($template);

            return $pdf->stream();
        } catch (\Throwable $th) {
            $response = new Response();
            $response->setStatus(400);
            $response->setMessage($th->getMessage());

            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }
}
