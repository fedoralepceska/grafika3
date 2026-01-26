<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tahoma:wght@400;700&display=swap" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Tahoma';

            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Tahoma';
            font-weight: bold;
            font-style: normal;
        }
        .tahoma {
            font-family: 'Tahoma', sans-serif;
            font-weight: 400; /* Regular */
        }

        /* Tahoma Bold */
        .tahoma-bold {
            font-family: 'Tahoma', sans-serif;
            font-weight: 700; /* Bold */
        }

        /* Open Sans Regular */
        .opensans {
            font-family: 'Open Sans', sans-serif;
            font-weight: 400; /* Regular */
        }

        /* Open Sans Bold */
        .opensans-bold {
            font-family: 'Open Sans', sans-serif;
            font-weight: 700; /* Bold */
        }
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: white;
            margin: -10px;
            font-size: 14px;
        }
        .order {
            padding-left: 5px;
        }

        .order1 {
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .left, .right {
            width: 45%;
            padding:0;
            margin: 0;
            line-height: 8pt;
            box-sizing: border-box; /* Ensure box sizing includes borders */
            font-size: 11pt;
        }
        .right{
            padding-left: 5px;
        }

        .left {
            border-right: 3px solid #cccccc; /* Add a vertical line to the right of .left */
        }

        .bolder {
            font-weight: bold;
        }

        .divider {
            height: 1px;
            background-color: #cccccc;
        }
        .job-table tr td:nth-child(2){
            border-bottom: 1px solid #f7f4f4;
            padding-left: 3px;
            font-size: 9.5pt;
        }
        .job-table tr td:nth-child(1){
            width: 160px;
            font-size: 9.5pt;
        }
        @page {
            size: A4; /* Set A4 size */
        }
        .page-break {
            page-break-after: always;
        }
        .copies-cell {
            border-bottom: 1px solid #f7f4f4;
        }
        .artboard-block {
            page-break-inside: avoid;
        }

    </style>
</head>
<body>
@foreach ($invoice->jobs as $job)
    <div class="invoice-info">
        <table  style="table-layout: fixed;  width: 100%;">
            <tr style=" line-height: 10px; margin-top: -25px" >
                <td style="width: 360px; text-align: left; margin-top: 5px">
                    <div>
                        <span class="tahoma" style="margin-left: -3px; color: #333333; font-size: 11.5pt;">Работен налог</span><span class="opensans" style="color: #333333;">:
                        </span> <span class="order tahoma bolder" style="font-size: 19pt">бр<span class="opensans" style="font-size: 27pt">.</span><span class="opensans bolder">{{ $invoice->order_number ?? $invoice->id }}/{{ $invoice->fiscal_year ?? date('Y', strtotime($invoice->start_date)) }}</span></span>
                    </div>
                </td>
                <td style="text-align: right" >
                    <img src="{{ public_path('logo_blue.png') }}" alt="LOGO" style="height: 30px;">
                </td>

        </table>
        <div class="divider"></div>
        <div class="info">
            <table style="width: 100%; color: #333333; gap: 0">
                <tr>
                    <td class="left" style="padding-top:5px; padding-bottom: 10px">
                        <div style="font-size:9.5pt" class="tahoma">Датум на отварање: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->start_date)) }}</span></div>
                        <div style="font-size:9.5pt" class="tahoma">Краен рок: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->end_date)) }}</span></div>
                        <div style="font-size:9.5pt" class="tahoma">Одговорно лице: <span class="opensans bolder">{{$invoice->user->name}}</span></div>
                    </td>
                    <td class="right"  style="padding-top:5px; padding-bottom: 10px">
                        <div class="bolder tahoma" style="text-transform: uppercase; font-size: 12px;">Нарачател: <span class="bolder">{{ $invoice->client->name }}</span></div>
                        <div style="font-size: 13px;">Контакт: <span>{{$invoice->contact->name }}</span></div>
                        <div style="font-size: 13px">Контакт тел: <span>{{$invoice->contact->phone }}</span> </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="divider"></div>
    </div>
    <div  class="bolder opensans" style="margin-left: 25px; margin-top: 4px; font-size: 10pt; color: #333333">
        РАБОТНА СТАВКА БР. <span class="opensans bolder" style="color: #333333; font-size: 10pt" >
        @php
            $totalJobs = $invoice->jobs->count();
            $currentJob = $loop->iteration;
            echo "({$currentJob}/{$totalJobs})";
        @endphp
        </span>
    </div>
        <div  class="opensans" style="margin-left: 25px; font-size: 9.5pt; color: #333333">
        Наслов на работниот налог: <span class="opensans bolder" style="  color: #333333 margin: 0; font-size: 9.5pt;  line-height: 70%;" >{{$invoice->invoice_title}}</span>
    </div>
    <div class="job-info" style="margin-left: 15px; margin-top: 10px">
        @php
            // Initialize dimensions breakdown variable for use throughout the job section
            $dimensionsBreakdown = is_string($job->dimensions_breakdown) ? json_decode($job->dimensions_breakdown, true) : $job->dimensions_breakdown;
        @endphp
        <table class="job-table" style="width: 100%; border-collapse: collapse; font-size: 10px" >
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc; margin: 0; font-size: 9.5pt">Производ</td>
                <td colspan="3"> {{ $job->name }}</td>
            </tr>

            @if($job->articles && $job->articles->count() > 0)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Оддел:</td>
                    <td colspan="3">
                        @php
                            // Determine department based on article types (small vs large materials)
                            $hasLargeFormat = false;
                            $hasSmallFormat = false;
                            foreach($job->articles as $article) {
                                if($article->largeFormatMaterial) {
                                    $hasLargeFormat = true;
                                }
                                if($article->smallMaterial) {
                                    $hasSmallFormat = true;
                                }
                            }
                        @endphp
                        @if($hasLargeFormat && $hasSmallFormat)
                            Large Format, Small Format
                        @elseif($hasLargeFormat)
                            Large Format
                        @elseif($hasSmallFormat)
                            Small Format
                        @else
                            Mixed Format
                        @endif
                    </td>
                </tr>
            @elseif($job->small_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Оддел:</td>
                    <td colspan="3">Small Format</td>
                </tr>
            @elseif($job->large_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Оддел:</td>
                    <td colspan="3">Large Format</td>
                </tr>
            @endif
            <tr >
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;;">Машина</td>
                <td colspan="3"> {{ $job->machinePrint }}</td>
            </tr>
            @if($job->articles && $job->articles->count() > 0)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Тип на материјал:</td>
                    <td colspan="3">
                        @php
                            $materialNames = [];
                            foreach($job->articles as $article) {
                                // Check if article has categories first
                                if($article->categories && $article->categories->count() > 0) {
                                    foreach($article->categories as $category) {
                                        if(!in_array($category->name, $materialNames)) {
                                            $materialNames[] = $category->name;
                                        }
                                    }
                                } else {
                                    // Fallback to material names if no categories
                                    if($article->largeFormatMaterial) {
                                        $materialNames[] = $article->largeFormatMaterial->name;
                                    } elseif($article->smallMaterial) {
                                        $materialNames[] = $article->smallMaterial->name;
                                    } else {
                                        $materialNames[] = $article->name;
                                    }
                                }
                            }
                            $materialNames = array_unique($materialNames);
                        @endphp
                        {{ implode(', ', $materialNames) }}
                    </td>
                </tr>
            @elseif($job->small_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Тип на материјал:</td>
                    <td colspan="3">{{ $job->small_material->name }}</td>
                </tr>
            @elseif($job->large_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Тип на материјал:</td>
                    <td colspan="3">{{ $job->large_material->name }}</td>
                </tr>
            @endif

            @if($job->question_answers)
                @php
                    $questionAnswers = is_string($job->question_answers) ? json_decode($job->question_answers, true) : $job->question_answers;
                    $answeredQuestions = [];
                    if ($questionAnswers) {
                        foreach ($questionAnswers as $questionId => $questionData) {
                            if (isset($questionData['question']) && isset($questionData['answer']) && $questionData['answer']) {
                                $answeredQuestions[] = $questionData['question'];
                            }
                        }
                    }
                @endphp
                @if(count($answeredQuestions) > 0)
                    <tr>
                        <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;;">Инфо:</td>
                        <td colspan="3" style="font-size: 9pt; line-height: 1.3;">
                            @foreach($answeredQuestions as $index => $question)
                                {{ $question }}@if(!$loop->last)<br>@endif
                            @endforeach
                        </td>
                    </tr>
                @endif
            @endif   
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Количина:</td>
                <td>{{ $job->quantity }}</td>

                <td class="tahoma" style="font-size: 9.5pt; background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc !important; width: 160px;">Копии:</td>
                <td class="copies-cell" style="padding-left: 3px; font-size: 9.5pt">{{ $job->copies }}</td>
            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Површина: </td>
                <td colspan="3">
                    @php
                        // Calculate total surface area using total_area_m2 from dimensions breakdown
                        $totalSurfaceArea = 0;
                        
                        if ($dimensionsBreakdown && is_array($dimensionsBreakdown) && count($dimensionsBreakdown) > 0) {
                            // Sum up total_area_m2 from all files in dimensions breakdown
                            foreach ($dimensionsBreakdown as $fileData) {
                                if (isset($fileData['total_area_m2'])) {
                                    $totalSurfaceArea += $fileData['total_area_m2'];
                                }
                            }
                        } else {
                            // Legacy system - use total_area_m2 from job
                            $totalSurfaceArea = $job->total_area_m2 ?? 0;
                        }
                        
                        // Multiply by copies for final surface area
                        $finalSurfaceArea = $totalSurfaceArea * $job->copies;
                        echo number_format($finalSurfaceArea, 5) . ' m²';
                    @endphp
                </td>
            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Назив</td>
                <td colspan="3">{{ $job->name }}</td>
            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Работни фајлови</td>
                <td colspan="3" >
                    @php
                        $workFileNames = [];
                        if ($dimensionsBreakdown && is_array($dimensionsBreakdown)) {
                            foreach ($dimensionsBreakdown as $item) {
                                if (is_array($item) && isset($item['filename']) && $item['filename']) {
                                    $workFileNames[] = $item['filename'];
                                }
                            }
                        }
                    @endphp
                    {{ !empty($workFileNames) ? implode(', ', array_unique($workFileNames)) : $job->file }}
                </td>

            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Коментар</td>
                <td colspan="3" style="line-height: 0.85; letter-spacing: 0%;">
                    @php
                        // Get job-specific notes first
                        $jobComments = [];
                        if ($job->notes && $job->notes->count() > 0) {
                            foreach ($job->notes as $note) {
                                if (!empty($note->comment)) {
                                    $jobComments[] = $note->comment;
                                }
                            }
                        }
                        
                        // Build display comment: job comments first, then order comment last
                        $displayParts = [];
                        if (!empty($jobComments)) {
                            $displayParts[] = implode(' | ', $jobComments);
                        }
                        if (!empty($invoice->comment)) {
                            $displayParts[] = $invoice->comment;
                        }
                        
                        $displayComment = implode(' | ', $displayParts);
                    @endphp
                    {{ $displayComment }}
                </td>
            </tr>
        </table>
    </div>
    <!-- <div  class="bolder opensans" style="margin-left: 15px; margin-top: 8px; font-size: 10pt; color: black">
         ДОРАБОТКА БР. <span class="opensans bolder" style="color: black; font-size: 10pt" >01</span>
        <div class="divider"></div>
    </div>
        <div style="margin-left: 15px; margin-top: 10px">
        <table style="border-collapse: collapse" >
            <tr>
                <td class="tahoma bolder" style="font-size: 7pt; background-color: #F0EFEF;letter-spacing: 1.5px; padding: 3px 3px 3px 5px;; color: black; text-align: right; !important width: 90px">ДОРАБОТКА</td>
                <td style="width: 160px; border-bottom: 1px solid #f7f4f4"> </td>

                <td class="tahoma bolder" style="font-size: 7pt; background-color: #F0EFEF;letter-spacing: 1.5px; padding: 3px 3px 3px 5px; !important; width: 110px; color: black; text-align: right; align-content: center; display: flex">ДОСТАВА</td>
                <td style="width: 160px; border-bottom: 1px solid #f7f4f4"> </td>
            </tr>
            <tr>
                <td style="height: 10px; !important width: 90px" ></td>
            </tr>
            <tr>
                <td class="tahoma bolder" style="background-color: #F0EFEF; font-size: 7pt; padding: 3px 5px 3px 110px; text-align: right; color: black;!important width: 90px">Достава</td>
                <td colspan="3">{{$job->shippingInfo}}</td>
            </tr>
        </table>
        </div> -->

    {{-- REFINEMENTS TIMELINE - SIMPLE INFORMATIONAL --}}
    @php
        // Access actions as array data (not Eloquent relationship)
        $jobArray = $job->toArray();
        $allActions = isset($jobArray['actions']) ? $jobArray['actions'] : [];
        $actionCount = count($allActions);
        
        // Extract action names
        $actionNames = array_column($allActions, 'name');
        
        // Filter out Start and Completed actions as requested
        $refinementActions = array_filter($allActions, function($action) {
            return !in_array(strtolower(trim($action['name'])), ['start', 'completed']);
        });
    @endphp
    
    @if(count($refinementActions) > 0)
        <div style="margin-left: 15px; margin-top: 15px; font-size: 10pt; color: #3f3f3f; font-family: 'Open Sans', sans-serif; font-weight: bold;">
            ДОРАБОТКИ <span style="color: #3f3f3f; font-size: 10pt; font-family: 'Open Sans', sans-serif; font-weight: bold;">:</span>
        </div>
        
        <div style="margin-left: 15px; margin-top: 10px; margin-bottom: 5px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    {{-- Timeline line row --}}
                    <td colspan="{{ count($refinementActions) }}" style="height: 2px; background-color: #cccccc; padding: 0; margin: 0;"></td>
                </tr>
                <tr>
                    {{-- Circle indicators --}}
                    @foreach($refinementActions as $index => $action)
                        <td style="text-align: center; vertical-align: top; padding: 0; margin: 0; width: {{ 100 / count($refinementActions) }}%;">
                            <div style="width: 25px; height: 25px; border-radius: 50%; background-color: #6c757d; border: 3px solid white; margin: -15px auto 8px auto; display: inline-block;"></div>
                        </td>
                    @endforeach
                </tr>
                <tr>
                    {{-- Action names --}}
                    @foreach($refinementActions as $index => $action)
                        <td style="text-align: center; vertical-align: top; padding: 5px; font-size: 9pt; color: #333; font-weight: bold; font-family: 'Tahoma', sans-serif; word-wrap: break-word; letter-spacing: 0%; line-height: 0.85;">
                            {{ $action['name'] }}
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    @php
        $originalFiles = is_array($job->originalFile) ? $job->originalFile : [];
        $hasMultipleFiles = count($originalFiles) > 0;
        $legacyFile = $job->file;
        $localThumbnails = isset($job->local_thumbnails) ? $job->local_thumbnails : [];
        

    @endphp

    @if ($hasMultipleFiles)
        {{-- Multiple files with thumbnails --}}
        @if ($dimensionsBreakdown && is_array($dimensionsBreakdown) && count($dimensionsBreakdown) > 0)
            {{-- Use dimensions breakdown data --}}
            @php
                // Get all thumbnails once for the entire job
                $allThumbnails = [];
                $thumbnailDir = public_path("jobfiles/thumbnails/{$job->id}");
                if (is_dir($thumbnailDir)) {
                    $thumbnailFiles = glob($thumbnailDir . '/*.png');
                    sort($thumbnailFiles); // Ensure consistent ordering
                    foreach ($thumbnailFiles as $thumbnailFile) {
                        if (file_exists($thumbnailFile)) {
                            $allThumbnails[] = base64_encode(file_get_contents($thumbnailFile));
                        }
                    }
                }
                
                // Fallback to local thumbnails if public path fails
                if (empty($allThumbnails)) {
                    foreach ($localThumbnails as $localThumbnail) {
                        if (file_exists($localThumbnail)) {
                            $allThumbnails[] = base64_encode(file_get_contents($localThumbnail));
                        }
                    }
                }
            @endphp
            
            @foreach ($dimensionsBreakdown as $index => $fileData)
                @php
                    // Get dimensions for this specific file
                    $pageDimensions = '';
                    if (isset($fileData['page_dimensions']) && is_array($fileData['page_dimensions']) && count($fileData['page_dimensions']) > 0) {
                        // Use the first page dimensions for this file
                        $pageDim = $fileData['page_dimensions'][0];
                        if (isset($pageDim['width_mm']) && isset($pageDim['height_mm'])) {
                            $pageDimensions = number_format($pageDim['width_mm'], 1) . 'x' . number_format($pageDim['height_mm'], 1) . ' mm';
                        }
                    }
                    
                    // Get the corresponding thumbnail for this file index
                    $thumbnailBase64 = isset($allThumbnails[$index]) ? $allThumbnails[$index] : null;
                @endphp
                
                @if ($thumbnailBase64)
                    <div class="artboard-block">
                        <div class="bolder tahoma" style="margin-top: 5px; font-size: 9.5pt; color: #3f3f3f;">
                            ART BOARD {{ $index + 1 }}@if($pageDimensions) - {{ $pageDimensions }}@endif<span class="opensans bolder" style="color: #333333; font-size: 10pt">:</span>
                        </div>
                        <div class="image-box" style="text-align: center; height: 370px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="data:image/png;base64,{{ $thumbnailBase64 }}" alt="Job Image {{ $index + 1 }}" style="max-width: 100%; max-height: 360px; object-fit: contain; vertical-align: middle;">
                        </div>
                        
                        {{-- Add control table after each art board --}}
                        <table style="width: 100%; text-align: center; letter-spacing: 0.5px; margin-top: 15px;">
                            <tr style="font-size: 11.5px; text-transform: uppercase">
                                <td class="tahoma" style="padding: 15px;">Печатење и контрола</td>
                                <td class="tahoma" style="padding: 15px;">Доработка и контрола</td>
                                <td class="tahoma" style="padding: 15px;">Монтажа и контрола</td>
                            </tr>
                            <tr style="">
                                <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                                <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                                <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            </tr>
                        </table>
                    </div>
                @endif
            @endforeach
        @else
            {{-- Fallback to original thumbnail loop --}}
            @php
                // Get all thumbnails once for the entire job
                $allThumbnails = [];
                $thumbnailDir = public_path("jobfiles/thumbnails/{$job->id}");
                if (is_dir($thumbnailDir)) {
                    $thumbnailFiles = glob($thumbnailDir . '/*.png');
                    sort($thumbnailFiles); // Ensure consistent ordering
                    foreach ($thumbnailFiles as $thumbnailFile) {
                        if (file_exists($thumbnailFile)) {
                            $allThumbnails[] = base64_encode(file_get_contents($thumbnailFile));
                        }
                    }
                }
                
                // Fallback to local thumbnails if available
                if (empty($allThumbnails)) {
                    foreach ($localThumbnails as $localThumbnail) {
                        if (file_exists($localThumbnail)) {
                            $allThumbnails[] = base64_encode(file_get_contents($localThumbnail));
                        }
                    }
                }
            @endphp
            
            @foreach ($originalFiles as $index => $filePath)
                @php
                    // Get the corresponding thumbnail for this file index
                    $thumbnailBase64 = isset($allThumbnails[$index]) ? $allThumbnails[$index] : null;
                @endphp
                
                @if ($thumbnailBase64)
                    <div class="artboard-block">
                        <div class="bolder tahoma" style="margin-top: 10px; font-size: 9.5pt; color: #3f3f3f">
                            ART BOARD {{ $index + 1 }}<span class="opensans bolder" style="color: #333333; font-size: 10pt">:</span>
                        </div>
                        <div class="image-box" style="text-align: center; max-height: 370px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                            <img src="data:image/png;base64,{{ $thumbnailBase64 }}" alt="Job Image {{ $index + 1 }}" style="max-width: 100%; max-height: 360px; object-fit: contain; vertical-align: middle;">
                        </div>
                        
                        {{-- Add control table after each art board --}}
                        <table style="width: 100%; text-align: center; letter-spacing: 0.5px; margin-top: 15px;">
                            <tr style="font-size: 11.5px; text-transform: uppercase">
                                <td class="tahoma" style="padding: 15px;">Печатење и контрола</td>
                                <td class="tahoma" style="padding: 15px;">Доработка и контрола</td>
                                <td class="tahoma" style="padding: 15px;">Монтажа и контрола</td>
                            </tr>
                            <tr>
                                <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                                <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                                <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            </tr>
                        </table>
                    </div>
                @endif
            @endforeach
        @endif
    @elseif ($hasMultipleFiles)
        {{-- Multiple files but no thumbnails available - show placeholders --}}
        @if ($dimensionsBreakdown && is_array($dimensionsBreakdown) && count($dimensionsBreakdown) > 0)
            {{-- Use dimensions breakdown data --}}
            @foreach ($dimensionsBreakdown as $index => $fileData)
                <div class="artboard-block">
                    <div  class="bolder tahoma" style="margin-top: 5px; font-size: 9.5pt; color: #3f3f3f">
                        ART BOARD {{ $index + 1 }}<span class="opensans bolder" style="color: #333333; font-size: 9pt" >:</span>
                        @if (isset($fileData['filename']))
                            <span style="font-size: 8pt; color: #666; margin-left: 10px;">{{ $fileData['filename'] }}</span>
                        @endif
                    </div>
                    <div style="text-align: center; height: 440px;">
                        <div style="max-height: 370px; min-height: 370px; vertical-align: middle; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc; background-color: #f9f9f9;">
                            <div style="text-align: center;">
                                <div style="font-size: 48px; color: #666; margin-bottom: 10px;">📄</div>
                                <div style="font-size: 14px; color: #666;">PDF File {{ $index + 1 }}</div>
                                @if (isset($fileData['filename']))
                                    <div style="font-size: 12px; color: #999;">{{ $fileData['filename'] }}</div>
                                @else
                                    <div style="font-size: 12px; color: #999;">{{ basename($originalFiles[$index] ?? 'Unknown File') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Add control table after each art board --}}
                    <table style="width: 100%; text-align: center; letter-spacing: 0.5px; margin-top: 15px;">
                        <tr style="font-size: 11.5px; text-transform: uppercase">
                            <td class="tahoma" style="padding: 15px;">Печатење и контрола</td>
                            <td class="tahoma" style="padding: 15px;">Доработка и контрола</td>
                            <td class="tahoma" style="padding: 15px;">Монтажа и контрола</td>
                        </tr>
                        <tr style="">
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                        </tr>
                    </table>
                </div>
                
                {{-- Add page break if not the last item --}}
                @if (!$loop->last)
                    <div style="page-break-after: always;"></div>
                @endif
            @endforeach
        @else
            {{-- Fallback to original files loop --}}
            @foreach ($originalFiles as $index => $filePath)
                <div class="artboard-block">
                    <div  class="bolder tahoma" style="margin-top: 5px; font-size: 9.5pt; color: #3f3f3f">
                        ART BOARD {{ $index + 1 }}<span class="opensans bolder" style="color: #333333; font-size: 9pt" >:</span>
                    </div>
                    <div style="text-align: center; height: 440px;">
                        <div style="max-height: 370px; min-height: 370px; vertical-align: middle; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc; background-color: #f9f9f9;">
                            <div style="text-align: center;">
                                <div style="font-size: 48px; color: #666; margin-bottom: 10px;">📄</div>
                                <div style="font-size: 14px; color: #666;">PDF File {{ $index + 1 }}</div>
                                <div style="font-size: 12px; color: #999;">{{ basename($filePath) }}</div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Add control table after each art board --}}
                    <table style="width: 100%; text-align: center; letter-spacing: 0.5px; margin-top: 15px;">
                        <tr style="font-size: 11.5px; text-transform: uppercase">
                            <td class="tahoma" style="padding: 15px;">Печатење и контрола</td>
                            <td class="tahoma" style="padding: 15px;">Доработка и контрола</td>
                            <td class="tahoma" style="padding: 15px;">Монтажа и контрола</td>
                        </tr>
                        <tr style="">
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                        </tr>
                    </table>
                </div>
                
                {{-- Add page break if not the last item --}}
                @if (!$loop->last)
                    <div style="page-break-after: always;"></div>
                @endif
            @endforeach
        @endif
    @elseif ($legacyFile)
        {{-- Legacy single file --}}
        @if ($dimensionsBreakdown && is_array($dimensionsBreakdown) && count($dimensionsBreakdown) > 0)
            {{-- Use dimensions breakdown data for single file --}}
            @foreach ($dimensionsBreakdown as $index => $fileData)
                <div class="artboard-block">
                    <div  class="bolder tahoma" style="margin-top: 13px; font-size: 9.5pt; color: #3f3f3f">
                        ART BOARD {{ $index + 1 }}<span class="opensans bolder" style="color: #333333; font-size: 10pt" >:</span>
                        @if (isset($fileData['filename']))
                            <span style="font-size: 8pt; color: #666; margin-left: 10px;">{{ $fileData['filename'] }}</span>
                        @endif
                    </div>
                    <div style="text-align: center; height: 440px;">
                        <img src="{{ storage_path('app/public/uploads/' . $legacyFile) }}" alt="Job Image {{ $index + 1 }}" style="max-height: 375px; min-height: 375px; vertical-align: middle;">
                    </div>
                    
                    {{-- Add control table after each art board --}}
                    <table style="width: 100%; text-align: center; letter-spacing: 0.5px; margin-top: 15px;">
                        <tr style="font-size: 11.5px; text-transform: uppercase">
                            <td class="tahoma" style="padding: 15px;">Печатење и контрола</td>
                            <td class="tahoma" style="padding: 15px;">Доработка и контрола</td>
                            <td class="tahoma" style="padding: 15px;">Монтажа и контрола</td>
                        </tr>
                        <tr style="">
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                        </tr>
                    </table>
                </div>
                
                {{-- Add page break if not the last item --}}
                @if (!$loop->last)
                    <div style="page-break-after: always;"></div>
                @endif
            @endforeach
        @else
            {{-- Fallback to original legacy file display --}}
            <div class="artboard-block">
                <div  class="bolder tahoma" style="margin-top: 13px; font-size: 9.5pt; color: #3f3f3f">
                    ART BOARD 1<span class="opensans bolder" style="color: #333333; font-size: 10pt" >:</span>
                </div>
                <div style="text-align: center; height: 440px;">
                    <img src="{{ storage_path('app/public/uploads/' . $legacyFile) }}" alt="Job Image" style="max-height: 375px; min-height: 375px; vertical-align: middle;">
                </div>
                
                {{-- Add control table for single legacy file --}}
                <table style="width: 100%; text-align: center; letter-spacing: 0.5px; margin-top: 15px;">
                    <tr style="font-size: 11.5px; text-transform: uppercase">
                        <td class="tahoma" style="padding: 15px;">Печатење и контрола</td>
                        <td class="tahoma" style="padding: 15px;">Доработка и контрола</td>
                        <td class="tahoma" style="padding: 15px;">Монтажа и контрола</td>
                    </tr>
                    <tr style="">
                        <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                        <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                        <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
                    </tr>
                </table>
            </div>
        @endif

    @endif

    @if (!empty($job->cutting_file_image))
        <div style="page-break-before: always;"></div>
        {{-- Duplicate header code here --}}
        <div class="invoice-info">
            <table  style="table-layout: fixed;  width: 100%;">
                <tr style=" line-height: 10px; margin-top: -25px" >
                    <td style="width: 360px; text-align: left; margin-top: 5px">
                        <div>
                            <span class="tahoma" style="margin-left: -3px; color: #333333; font-size: 11.5pt;">Работен налог</span><span class="opensans" style="color: #333333;">:
                            </span> <span class="order tahoma bolder" style="font-size: 19pt">бр<span class="opensans" style="font-size: 27pt">.</span><span class="opensans bolder">{{ $invoice->id }}/{{ date('Y', strtotime($invoice->start_date)) }}</span></span>
                        </div>
                    </td>
                    <td style="text-align: right" >
                        <img src="{{ public_path('logo_blue.png') }}" alt="LOGO" style="height: 30px;">
                    </td>

            </table>
            <div class="divider"></div>
            <div class="info">
                <table style="width: 100%; color: #333333; gap: 0">
                    <tr>
                        <td class="left" style="padding-top:5px; padding-bottom: 10px">
                            <div style="font-size:9.5pt" class="tahoma">Датум на отварање: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->start_date)) }}</span></div>
                            <div style="font-size:9.5pt" class="tahoma">Краен рок: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->end_date)) }}</span></div>
                            <div style="font-size:9.5pt" class="tahoma">Одговорно лице: <span class="opensans bolder">{{$invoice->user->name}}</span></div>
                        </td>
                        <td class="right"  style="padding-top:5px; padding-bottom: 10px">
                            <div class="bolder tahoma" style="text-transform: uppercase; font-size: 12px;">Нарачател: <span class="bolder">{{ $invoice->client->name }}</span></div>
                            <div style="font-size: 13px;">Контакт: <span>{{$invoice->contact->name }}</span></div>
                            <div style="font-size: 13px">Контакт тел: <span>{{$invoice->contact->phone }}</span> </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="divider"></div>
        </div>
        <div  class="bolder opensans" style="margin-left: 25px; margin-top: 8px; font-size: 10pt; color: #333333">
        CUTTING FILE:
    </div>
        <div style="text-align: center;">
            <img src="{{ $job->cutting_file_image }}" style="max-width: 100%; max-height: 900px;">
        </div>
        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @elseif (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach

{{-- Mockup Section - Only once at the end --}}
@if ($invoice->mockup && file_exists(public_path('mockups/' . $invoice->mockup)))
    <div style="page-break-before: always;"></div>
    {{-- Duplicate header for mockup page --}}
    <div class="invoice-info">
        <table style="table-layout: fixed; width: 100%;">
            <tr style="line-height: 10px; margin-top: -25px">
                <td style="width: 360px; text-align: left; margin-top: 5px">
                    <div>
                        <span class="tahoma" style="margin-left: -3px; color: #333333; font-size: 11.5pt;">Работен налог</span><span class="opensans" style="color: #333333;">:
                        </span> <span class="order tahoma bolder" style="font-size: 19pt">бр<span class="opensans" style="font-size: 27pt">.</span><span class="opensans bolder">{{ $invoice->order_number ?? $invoice->id }}/{{ $invoice->fiscal_year ?? date('Y', strtotime($invoice->start_date)) }}</span></span>
                    </div>
                </td>
                <td style="text-align: right">
                    <img src="{{ public_path('logo_blue.png') }}" alt="LOGO" style="height: 30px;">
                </td>
            </tr>
        </table>
        <div class="divider"></div>
        <div class="info">
            <table style="width: 100%; color: #333333; gap: 0">
                <tr>
                    <td class="left" style="padding-top:5px; padding-bottom: 10px">
                        <div style="font-size:9.5pt" class="tahoma">Датум на отварање: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->start_date)) }}</span></div>
                        <div style="font-size:9.5pt" class="tahoma">Краен рок: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->end_date)) }}</span></div>
                        <div style="font-size:9.5pt" class="tahoma">Одговорно лице: <span class="opensans bolder">{{$invoice->user->name}}</span></div>
                    </td>
                    <td class="right" style="padding-top:5px; padding-bottom: 10px">
                        <div class="bolder tahoma" style="text-transform: uppercase; font-size: 12px;">Нарачател: <span class="bolder">{{ $invoice->client->name }}</span></div>
                        <div style="font-size: 13px;">Контакт: <span>{{$invoice->contact->name }}</span></div>
                        <div style="font-size: 13px">Контакт тел: <span>{{$invoice->contact->phone }}</span> </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="divider"></div>
    </div>
    
    {{-- Mockup Label --}}
    <div class="bolder tahoma" style="margin-left: 25px; margin-top: 8px; font-size: 10pt; color: #3f3f3f">
        MOCKUP<span class="opensans bolder" style="color: #333333; font-size: 10pt">:</span>
    </div>
    
    {{-- Mockup Image Container --}}
    @php
        $mockupPath = public_path('mockups/' . $invoice->mockup);
        $mockupBase64 = null;
        
        if (file_exists($mockupPath)) {
            // Encode image as base64 for DomPDF compatibility (same as ART BOARD images)
            $imageContent = file_get_contents($mockupPath);
            $imageType = strtolower(pathinfo($mockupPath, PATHINFO_EXTENSION));
            $mimeType = $imageType === 'jpg' || $imageType === 'jpeg' ? 'image/jpeg' : 'image/png';
            $mockupBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
        }
    @endphp
    
    @if ($mockupBase64)
        <div class="artboard-block" style="margin-top: 10px;">
            <div class="image-box" style="text-align: center; height: 440px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                <img src="{{ $mockupBase64 }}" 
                     alt="Order Mockup" 
                     style="max-width: 100%; max-height: 430px; object-fit: contain; vertical-align: middle;">
            </div>
        </div>
    @endif
@endif
</body>
</html>
