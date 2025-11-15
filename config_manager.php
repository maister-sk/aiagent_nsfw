<?php
    // Common Includes
    $enginePath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;
    require_once $enginePath . "conf" . DIRECTORY_SEPARATOR . "conf.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "model_dynmodel.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "{$GLOBALS["DBDRIVER"]}.class.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "chat_helper_functions.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "data_functions.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "logger.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "utils_game_timestamp.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "rolemaster_helpers.php";
    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "dynamic_update_util.php";
    $GLOBALS["ENGINE_PATH"] = $enginePath;

    // Global DB object
    $db = new sql();

    require_once $enginePath . "lib/core/npc_master.class.php";
    require_once $enginePath . "lib/core/api_badge.class.php";
    require_once $enginePath . "lib/core/core_profiles.class.php";
    require_once $enginePath . "lib/core/llm_connector.class.php";
    require_once $enginePath . "lib/core/tts_connector.class.php";

    require_once $enginePath . "lib" . DIRECTORY_SEPARATOR . "lazy_xml.php";

    // Global DB object
    $db            = new sql();
    $GLOBALS["db"] = $db;

    // Handle AJAX requests
    $action = $_GET['action'] ?? null;

    if ($action === 'read') {
        handleRead();
    } elseif ($action === 'create') {
        handleCreate();
    } elseif ($action === 'update') {
        handleUpdate();
    } elseif ($action === 'delete') {
        handleDelete();
    } elseif ($action === 'loadNPCs') {
        handleLoadNPCs();
    } elseif ($action === 'loadConnectors') {
        handleLoadConnectors();
    } elseif ($action === 'submitToolsForm') {
        handleSubmitToolsForm();
    } elseif ($action === 'loadSettings') {
        handleLoadSettings();
    } elseif ($action === 'saveSettings') {
        handleSaveSettings();
    } elseif ($action === 'generateTable') {
        handleGenerateTable();
    } elseif ($action === 'importData') {
        handleImportData();
    }

    // CRUD Functions
    function handleImportData()
    {
        try {
            if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }

            $file = $_FILES['importFile']['tmp_name'];
            if (!is_readable($file)) {
                throw new Exception('Cannot read uploaded file');
            }

            $lines = file($file, FILE_SKIP_EMPTY_LINES);
            if (empty($lines)) {
                throw new Exception('File is empty');
            }

            // Parse header row
            $headerLine = trim($lines[0]);
            $headers = str_getcsv($headerLine, "\t", '"');
            $headers = array_map('trim', $headers);

            if (empty($headers) || !in_array('stage', $headers)) {
                throw new Exception('Invalid file format: missing "stage" field in header');
            }

            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];

            // Process data rows
            for ($i = 1; $i < count($lines); $i++) {
                try {
                    $dataLine = trim($lines[$i]);
                    if (empty($dataLine)) {
                        continue;
                    }

                    $values = str_getcsv($dataLine, "\t", '"');
                    $values = array_map('trim', $values);

                    if (count($values) !== count($headers)) {
                        $skippedCount++;
                        continue;
                    }

                    // Create associative array
                    $row = array_combine($headers, $values);

                    // Handle \N as NULL
                    foreach ($row as $key => $value) {
                        if ($value === '\N' || $value === 'NULL' || $value === 'null') {
                            $row[$key] = null;
                        }
                    }

                    if (empty($row['stage'])) {
                        $skippedCount++;
                        continue;
                    }

                    // Only include valid columns
                    $validColumns = ['stage', 'description', 'description_es', 'description_en', 'i_desc'];
                    $insertData = [];
                    foreach ($validColumns as $col) {
                        if (isset($row[$col])) {
                            $insertData[$col] = $row[$col];
                        }
                    }

                    // Insert or update the row
                    try {
                        $GLOBALS["db"]->insert('ext_aiagentnsfw_scenes', $insertData);
                        $importedCount++;
                    } catch (Exception $insertError) {
                        // Check if it's a duplicate key error, if so try updating
                        if (strpos($insertError->getMessage(), 'duplicate') !== false || 
                            strpos($insertError->getMessage(), 'unique') !== false ||
                            strpos($insertError->getMessage(), 'already exists') !== false) {
                            
                            $set = [];
                            foreach (['description', 'description_es', 'description_en', 'i_desc'] as $col) {
                                if (isset($insertData[$col])) {
                                    $val = is_null($insertData[$col]) ? 'NULL' : "'" . $GLOBALS["db"]->escape($insertData[$col]) . "'";
                                    $set[] = "$col=$val";
                                }
                            }

                            if (!empty($set)) {
                                $setStr = implode(', ', $set);
                                $where = "stage='" . $GLOBALS["db"]->escape($insertData['stage']) . "'";
                                $GLOBALS["db"]->update('ext_aiagentnsfw_scenes', $setStr, $where);
                                $importedCount++;
                            } else {
                                $skippedCount++;
                            }
                        } else {
                            throw $insertError;
                        }
                    }
                } catch (Exception $rowError) {
                    $errors[] = "Row " . ($i + 1) . ": " . $rowError->getMessage();
                }
            }

            $message = "Import completed. Imported/Updated: $importedCount, Skipped: $skippedCount";
            if (!empty($errors)) {
                $message .= ". Errors: " . implode("; ", array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= " (and " . (count($errors) - 5) . " more)";
                }
            }

            echo json_encode([
                'success' => true,
                'message' => $message,
                'imported' => $importedCount,
                'skipped' => $skippedCount,
                'errors' => $errors,
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }
    function handleGenerateTable()
    {
        try {
            $sql = <<<SQL
CREATE TABLE IF NOT EXISTS public.ext_aiagentnsfw_scenes (
    stage text NOT NULL,
    description text,
    description_es text,
    description_en text,
    i_desc text
);

ALTER TABLE public.ext_aiagentnsfw_scenes OWNER TO dwemer;

COMMENT ON TABLE public.ext_aiagentnsfw_scenes IS 'ostim scenes descriptions';

ALTER TABLE ONLY public.ext_aiagentnsfw_scenes
    ADD CONSTRAINT ext_aiagentnsfw_scenes_pkey PRIMARY KEY (stage);
SQL;

            // Execute the SQL statement
            $GLOBALS["db"]->query($sql);

            echo json_encode([
                'success' => true,
                'message' => 'Table ext_aiagentnsfw_scenes created successfully',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }
    function handleRead()
    {
        try {
            $query   = "SELECT * FROM ext_aiagentnsfw_scenes ORDER BY description asc nulls first,stage";
            $results = $GLOBALS["db"]->fetchAll($query);
            echo json_encode([
                'success' => true,
                'data'    => $results,
            ]);
        } catch (Exception $e) {
            // Check if the table exists
            $tableExistsQuery = "SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'ext_aiagentnsfw_scenes'
            )";
            
            $tableExists = false;
            try {
                $result = $GLOBALS["db"]->fetchOne($tableExistsQuery);
                $tableExists = isset($result[0]) ? (bool)$result[0] : false;
            } catch (Exception $checkException) {
                // If we can't check, assume table doesn't exist
                $tableExists = false;
            }
            
            $errorMessage = $e->getMessage();
            if (!$tableExists) {
                $errorMessage .= ' [TABLE NOT FOUND: Please create the ext_aiagentnsfw_scenes table first using the "Generate ext_aiagentnsfw_scenes Table" button in Settings]';
            }
            
            echo json_encode([
                'success' => false,
                'error'   => $errorMessage,
                'tableExists' => $tableExists,
            ]);
        }
        exit;
    }

    function handleCreate()
    {
        try {
            $stage          = $_POST['stage'] ?? '';
            $description    = $_POST['description'] ?? '';
            $description_es = $_POST['description_es'] ?? '';
            $description_en = $_POST['description_en'] ?? '';
            $i_desc         = $_POST['i_desc'] ?? '';

            if (empty($stage)) {
                throw new Exception('Stage is required');
            }

            $data = [
                'stage'          => $stage,
                'description'    => $description,
                'description_es' => $description_es,
                'description_en' => $description_en,
                'i_desc'         => $i_desc,
            ];

            $GLOBALS["db"]->insert('ext_aiagentnsfw_scenes', $data);

            echo json_encode([
                'success' => true,
                'message' => 'Scene created successfully',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }

    function handleUpdate()
    {
        try {
            $stage          = $_POST['stage'] ?? '';
            $description    = $_POST['description'] ?? '';
            $description_es = $_POST['description_es'] ?? '';
            $description_en = $_POST['description_en'] ?? '';
            $i_desc         = $_POST['i_desc'] ?? '';

            if (empty($stage)) {
                throw new Exception('Stage is required');
            }

            $set = "description='" . $GLOBALS["db"]->escape($description) . "', " .
            "description_es='" . $GLOBALS["db"]->escape($description_es) . "', " .
            "description_en='" . $GLOBALS["db"]->escape($description_en) . "', " .
            "i_desc='" . $GLOBALS["db"]->escape($i_desc) . "'";

            $where = "stage='" . $GLOBALS["db"]->escape($stage) . "'";

            $GLOBALS["db"]->update('ext_aiagentnsfw_scenes', $set, $where);

            echo json_encode([
                'success' => true,
                'message' => 'Scene updated successfully',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }

    function handleDelete()
    {
        try {
            $stage = $_POST['stage'] ?? '';

            if (empty($stage)) {
                throw new Exception('Stage is required');
            }

            $where = "stage='" . $GLOBALS["db"]->escape($stage) . "'";
            $GLOBALS["db"]->delete('ext_aiagentnsfw_scenes', $where);

            echo json_encode([
                'success' => true,
                'message' => 'Scene deleted successfully',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }

    function handleLoadNPCs()
    {
        try {
            $query   = "SELECT id, npc_name FROM core_npc_master ORDER BY npc_name";
            $results = $GLOBALS["db"]->fetchAll($query);
            echo json_encode([
                'success' => true,
                'data'    => $results,
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }

    function handleLoadConnectors()
    {
        try {
            $query   = "SELECT id, label FROM core_llm_connector ORDER BY label";
            $results = $GLOBALS["db"]->fetchAll($query);
            echo json_encode([
                'success' => true,
                'data'    => $results,
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }

    function handleSubmitToolsForm()
    {
        try {
            // TODO: Implement the logic to process the tools form submission
            // Expected POST parameters:
            // - npc_id: NPC ID
            // - connector_id: Connector ID
            // - profanity_level: Profanity level (suave, normal, duro, extaduro)
            // - sex_prompt: Generated sex prompt text
            // - sex_speech_style: Generated sex speech style text
            $npcMaster = new NpcMaster();
            $currentNpcData = $npcMaster->getById($_POST["npc_id"]);
            $extended_data=$npcMaster->getExtendedData($currentNpcData);

            $extended_data["sex_prompt"]=$_POST["sex_prompt"];
            $extended_data["sex_speech_style"]=$_POST["sex_speech_style"];

            $currentNpcData=$npcMaster->setExtendedData($currentNpcData,$extended_data);
            $npcMaster->updateByArray($currentNpcData);

            echo json_encode([
                'success' => true,
                'message' => 'Tools form submitted successfully',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
        exit;
    }

    function handleLoadSettings()
    {
        try {
            $settingsRow = $GLOBALS["db"]->fetchOne("SELECT value FROM conf_opts WHERE id = 'aiagent_nsfw_settings'");
            
            $settings = [
                'XTTS_MODIFY_LEVEL1' => false,
                'XTTS_MODIFY_LEVEL2' => false,
                'GENERIC_GLOSSARY' => '',
                'TRACK_DRUNK_STATUS' => false,
                'TRACK_FERTILITY_INFO' => false,
            ];

            if ($settingsRow && !empty($settingsRow['value'])) {
                $parsedSettings = json_decode($settingsRow['value'], true);
                if (is_array($parsedSettings)) {
                    $settings = array_merge($settings, $parsedSettings);
                }
            }

            echo json_encode([
                'success' => true,
                'data' => $settings,
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
        exit;
    }

    function handleSaveSettings()
    {
        try {
            $settings = [
                'XTTS_MODIFY_LEVEL1' => isset($_POST['XTTS_MODIFY_LEVEL1']) ? filter_var($_POST['XTTS_MODIFY_LEVEL1'], FILTER_VALIDATE_BOOLEAN) : false,
                'XTTS_MODIFY_LEVEL2' => isset($_POST['XTTS_MODIFY_LEVEL2']) ? filter_var($_POST['XTTS_MODIFY_LEVEL2'], FILTER_VALIDATE_BOOLEAN) : false,
                'GENERIC_GLOSSARY' => $_POST['GENERIC_GLOSSARY'] ?? '',
                'TRACK_DRUNK_STATUS' => isset($_POST['TRACK_DRUNK_STATUS']) ? filter_var($_POST['TRACK_DRUNK_STATUS'], FILTER_VALIDATE_BOOLEAN) : false,
                'TRACK_FERTILITY_INFO' => isset($_POST['TRACK_FERTILITY_INFO']) ? filter_var($_POST['TRACK_FERTILITY_INFO'], FILTER_VALIDATE_BOOLEAN) : false,
            ];

            $jsonSettings = json_encode($settings);

            $GLOBALS["db"]->upsertRowOnConflict(
                'conf_opts',
                [
                    'id' => 'aiagent_nsfw_settings',
                    'value' => $jsonSettings,
                ],
                'id'
            );

            echo json_encode([
                'success' => true,
                'message' => 'Settings saved successfully',
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
        exit;
    }

    // If we get here, render the HTML page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NSFW Agent Configuration Manager</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🍆</text></svg>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            background: #f5f5f5;
        }

        .tab-button {
            flex: 1;
            padding: 15px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }

        .tab-button:hover {
            color: #667eea;
            background: #fff;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: white;
        }

        .tab-content {
            display: none;
            padding: 30px;
            animation: fadeIn 0.3s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 13px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
            font-family: inherit;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 200px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .btn-danger {
            background: #ff6b6b;
            color: white;
        }

        .btn-danger:hover {
            background: #ff5252;
        }

        .btn-warning {
            background: #ffa502;
            color: white;
        }

        .btn-warning:hover {
            background: #ff9500;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 13px;
            display: none;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        table thead {
            background: #f5f5f5;
            border-bottom: 2px solid #ddd;
        }

        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            color: #666;
        }

        table tr:hover {
            background: #f9f9f9;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons button {
            padding: 6px 12px;
            font-size: 12px;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #667eea;
        }

        .loading.active {
            display: block;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .row.full {
            grid-template-columns: 1fr;
        }

        .searchable-select-wrapper {
            position: relative;
            margin-bottom: 15px;
        }

        .searchable-select-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
            font-family: inherit;
        }

        .searchable-select-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .searchable-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .searchable-select-dropdown.active {
            display: block;
        }

        .searchable-select-option {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .searchable-select-option:hover {
            background: #f5f5f5;
        }

        .searchable-select-option.selected {
            background: #e8eef7;
            color: #667eea;
            font-weight: 600;
        }

        .textarea-with-button {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .textarea-with-button textarea {
            flex: 1;
            min-width:900px;
            min-height:50px
        }

        .textarea-with-button button {
            padding: 10px 15px;
            height: fit-content;
            white-space: nowrap;
            margin-top: 0;
        }

        p.legend {
            font-family:"Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            font-size:13px;
            padding-bottom:20px;;
            padding-top:20px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination button,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            background: white;
            color: #333;
            transition: all 0.2s ease;
        }

        .pagination button:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
            font-weight: 600;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination span {
            cursor: default;
            border: none;
            padding: 8px 0;
        }

        .pagination-info {
            text-align: center;
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }

        @media (max-width: 768px) {
            .row {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-direction: column;
            }

            .header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎭 NSFW Agent Configuration</h1>
            <p>Manage scenes and configurations</p>
        </div>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('scenes')">📋 Scenes Manager</button>
            <button class="tab-button" onclick="switchTab('tools')">🛠️ Tools</button>
            <button class="tab-button" onclick="switchTab('settings')">⚙️ Settings</button>
            <button class="tab-button" onclick="switchTab('info')">ℹ️ Info</button>
        </div>

        <!-- Scenes Tab -->
        <div id="scenes" class="tab-content active">
            <div class="alert success" id="sceneSuccessAlert"></div>
            <div class="alert error" id="sceneErrorAlert"></div>

            <h2 style="margin-bottom: 20px; color: #333;">Create New Scene</h2>

            <div class="row">
                <div class="form-group">
                    <label for="sceneStage">Stage (ID) *</label>
                    <input type="text" id="sceneStage" placeholder="e.g., scene_01" required>
                </div>
                <div class="form-group">
                    <label for="sceneDesc">Description</label>
                    <input type="text" id="sceneDesc" placeholder="Default description">
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <label for="sceneDescEs">Description (Spanish)</label>
                    <input type="text" id="sceneDescEs" placeholder="Spanish description">
                </div>
                <div class="form-group">
                    <label for="sceneDescEn">Description (English)</label>
                    <input type="text" id="sceneDescEn" placeholder="English description">
                </div>
            </div>

            <div class="row full">
                <div class="form-group">
                    <label for="sceneIDesc">Internal Description</label>
                    <textarea id="sceneIDesc" placeholder="Internal/technical description"></textarea>
                </div>
            </div>

            <div class="button-group">
                <button class="btn-primary" onclick="createScene()">➕ Create Scene</button>
                <button class="btn-secondary" onclick="clearSceneForm()">🔄 Clear Form</button>
                <button class="btn-warning" onclick="generateSceneDescriptions()" title='Will Use AI'>⚙️ ($) Generate descriptions from internal desc</button>
            </div>
            <p class="legend">Generate descriptions - what is this for? You can edit the internal description field and add a natural speaking description, 
                just refer to actors as "actor zero", "actor one". Then use the button to generate a well-formatted description. 
                You can use a browser extension like <a target="_blank" href="https://chromewebstore.google.com/detail/voice-in-speech-to-text-d/pjnefijmagpdjfhhkpljicbbpicelgko">Voice In - Speech to Text</a>
                to fill the internal description field more quickly using voice.
            </p>

            <h2 style="margin: 30px 0 20px; color: #333;">Existing Scenes</h2>
            <div class="loading active" id="scenesLoading">Loading scenes...</div>
            <div class="table-wrapper">
                <table id="scenesTable" style="display: none;">
                    <thead>
                        <tr>
                            <th>Stage</th>
                            <th>Description</th>
                            <th>Description (ES)</th>
                            <th>Description (EN)</th>
                            <th>Internal Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="scenesTableBody">
                    </tbody>
                </table>
            </div>
            <div id="paginationContainer" style="display: none;">
                <div class="pagination" id="paginationControls"></div>
                <div class="pagination-info" id="paginationInfo"></div>
            </div>
        </div>

        <!-- Tools Tab -->
        <div id="tools" class="tab-content">
            <div class="alert success" id="toolsSuccessAlert"></div>
            <div class="alert error" id="toolsErrorAlert"></div>

            <h2 style="margin-bottom: 20px; color: #333;">NPC Prompt Generator</h2>
            <p class="legend">This a tool to set extended NPC properties that will apply on intimate Scenes only. Changes here can be edited on NPC sheet too, on extended data. Use The generate buttons to use the selected connector to generate content</p>
            <div class="form-group">
                <label for="npcSelect">Select NPC *</label>
                <div class="searchable-select-wrapper">
                    <input
                        type="text"
                        id="npcSelectInput"
                        class="searchable-select-input"
                        placeholder="Search NPCs..."
                        autocomplete="off"
                    >
                    <div class="searchable-select-dropdown" id="npcSelectDropdown"></div>
                    <input type="hidden" id="npcSelectValue">
                </div>
            </div>

            <div class="form-group">
                <label for="connectorSelect">Select Connector *</label>
                <div class="searchable-select-wrapper">
                    <input
                        type="text"
                        id="connectorSelectInput"
                        class="searchable-select-input"
                        placeholder="Search Connectors..."
                        autocomplete="off"
                    >
                    <div class="searchable-select-dropdown" id="connectorSelectDropdown"></div>
                    <input type="hidden" id="connectorSelectValue">
                </div>
            </div>

            <div class="form-group">
                <label for="profanityLevel">Profanity Level *</label>
                <select id="profanityLevel" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; font-family: inherit;">
                    <option value="">-- Select profanity level --</option>
                    <option value="0">soft</option>
                    <option value="1">neutral</option>
                    <option value="2">hard</option>
                    <option value="3">naugthy</option>
                </select>
            </div>

            <div class="textarea-with-button">
                <div style="flex: 1;">
                    <label for="sexPrompt" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Sex Prompt</label>
                    <textarea id="sexPrompt" placeholder="Enter sex prompt..."></textarea>
                </div>
                <button class="btn-secondary" onclick="generatePrompt('sex_prompt')" style="align-self: center;">🔄 Generate</button>
            </div>

            <div class="textarea-with-button">
                <div style="flex: 1;">
                    <label for="sexSpeechStyle" style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Sex Speech Style</label>
                    <textarea id="sexSpeechStyle" placeholder="Enter sex speech style..."></textarea>
                </div>
                <button class="btn-secondary" onclick="generatePrompt('sex_speech_style')" style="align-self: center;">🔄 Generate</button>
            </div>

            <div class="button-group">
                <button class="btn-primary" onclick="submitToolsForm()">✅ Submit</button>
                <button class="btn-secondary" onclick="clearToolsForm()">🔄 Clear Form</button>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings" class="tab-content">
            <div class="alert success" id="settingsSuccessAlert"></div>
            <div class="alert error" id="settingsErrorAlert"></div>

            <h2 style="margin-bottom: 20px; color: #333;">General Settings</h2>

            <div class="form-group">
                <p class="legend">NPCs will talk slower when in idle scenes. Only applies to XTTS</p>
                <label for="xttsModifyLevel1" style="">
                    <input type="checkbox" id="xttsModifyLevel1" name="XTTS_MODIFY_LEVEL1">
                    <span>XTTS Modify Level 1</span>
                </label>
            </div>

            <div class="form-group">
                <p class="legend">NPCs will talk slower, and using moans and gasps, when in action scenes. Only applies to XTTS</p>
                <label for="xttsModifyLevel2" style="">
                    <input type="checkbox" id="xttsModifyLevel2" name="XTTS_MODIFY_LEVEL2">
                    <span>XTTS Modify Level 2</span>
                </label>
            </div>

            <div class="form-group">
                <label for="trackDrunkStatus" style="">
                    <input type="checkbox" id="trackDrunkStatus" name="TRACK_DRUNK_STATUS">
                    <span>Track Drunk Status</span>
                </label>
            </div>

            <div class="form-group">
                <label for="trackFertilityInfo" style="">
                    <input type="checkbox" id="trackFertilityInfo" name="TRACK_FERTILITY_INFO">
                    <span>Track Fertility Info</span>
                </label>
            </div>

            <div class="row full">
                <p class="legend">Comma separated terms, just to use when calling AI in this UI to generate content</p>
                <div class="form-group">
                    <label for="genericGlossary">Generic Glossary</label>
                    <textarea id="genericGlossary" placeholder="Enter glossary terms..." style="min-height: 200px;"></textarea>
                    
                </div>
            </div>

            <h2 style="margin: 30px 0 20px; color: #333;">Database Management</h2>

            <h3 style="margin: 20px 0 15px; color: #555; font-size: 15px;">Generate Table</h3>
            <p class="legend">Create the ext_aiagentnsfw_scenes table in the database if it doesn't exist.</p>
            <div class="button-group">
                <button class="btn-warning" onclick="generateTable()">📊 Generate ext_aiagentnsfw_scenes Table</button>
            </div>

            <h3 style="margin: 20px 0 15px; color: #555; font-size: 15px;">Import Scenes from File</h3>
            <p class="legend">Import scenes from a tab-separated file with quoted fields. First row should contain field names: stage, description, description_es, description_en, i_desc. Use \N for NULL values.</p>
            <div class="form-group">
                <label for="importFile">Select TSV File</label>
                <input type="file" id="importFile" accept=".tsv,.txt" />
            </div>
            <div class="button-group">
                <button class="btn-primary" onclick="importScenes()">� Import Scenes</button>
            </div>

            <h3 style="margin: 20px 0 15px; color: #555; font-size: 15px;">Settings</h3>
            <div class="button-group">
                <button class="btn-primary" onclick="saveSettings()">� Save Settings</button>
                <button class="btn-secondary" onclick="resetSettings()">🔄 Reload Settings</button>
            </div>
        </div>

        <!-- Info Tab -->
        <div id="info" class="tab-content">
            <h2 style="margin-bottom: 20px; color: #333;">📚 NSFW Agent Documentation</h2>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">Overview</h3>
            <p style="line-height: 1.6; color: #666; margin-bottom: 15px;">
                This extension integrates intimate content with Ostim animations. NPCs become aware of player ostim scenes and can perform adult actions. 
                By default, actions are not available and are progressively enabled as the NPC's <strong>sex_disposal</strong> property increases through seduction gameplay and relaxing status.
            </p>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">Core Concepts</h3>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                <p><strong>Animation Types:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px; color: #666;">
                    <li>NPCs can start animations directly</li>
                    <li>NPCs can initiate idle scenes</li>
                    <li>NPCs can change animations based on chat interaction</li>
                </ul>
            </div>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">NPC Extended Data</h3>
            <p style="color: #666; margin-bottom: 10px;">Extended NPC data stores intimate status and properties:</p>
            <div style="background: #f0f4ff; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-family: monospace; font-size: 12px; color: #333; border: 1px solid #ddd; overflow-x: auto;">
                <div><strong>aiagent_nsfw_intimacy_data:</strong> {</div>
                <div style="margin-left: 20px;">
                    <div><strong>level:</strong> 0-2 (0: not in scene, 1: idle scene, 2: active scene)</div>
                    <div><strong>is_naked:</strong> 0|1 (tracks PutOffClothes/PutOnClothes actions)</div>
                    <div><strong>orgasmed:</strong> boolean (true if NPC climaxed in session)</div>
                    <div><strong>sex_disposal:</strong> 0-100 (above 10, sex actions become available)</div>
                    <div><strong>orgasm_generated:</strong> boolean (precached climax speech)</div>
                    <div><strong>orgasm_generated_text:</strong> string (generated climax dialogue)</div>
                    <div><strong>adult_entertainment_services_autodetected:</strong> boolean (sexual worker marker)</div>
                </div>
                <div>}</div>
            </div>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">NPC Configuration</h3>
            <p style="color: #666; margin-bottom: 10px;">Two key extended NPC properties:</p>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                <p><strong>sex_prompt:</strong> Prompt used when NPC is in an ostim scene (configure in Tools tab)</p>
                <p style="margin-top: 10px;"><strong>sex_speech_style:</strong> Speech style for adult dialogue (configure in Tools tab)</p>
            </div>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">Importing Rules</h3>
            <p style="color: #666; margin-bottom: 10px;">Automate NPC categorization through import rules. Example - assign all females from "Ancient Profession" mod to profile 6:</p>
            <div style="background: #f0f4ff; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-family: monospace; font-size: 11px; color: #333; border: 1px solid #ddd; overflow-x: auto;">
                <div>id | description | match_name | match_race | match_gender | match_base | match_mods | action | profile | priority | enabled</div>
                <div style="margin-top: 5px; border-top: 1px solid #ddd; padding-top: 5px;">
                    2 | Ancient Profession | .* | .* | female | .* | {prostitutes.esp} | {"metadata": {"rule_applied": true}} | 6 | 1 | TRUE
                </div>
            </div>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">Profile Configuration</h3>
            <p style="color: #666; margin-bottom: 10px;">At the target profile (e.g., profile 6), set metadata properties:</p>
            <div style="background: #f0f4ff; padding: 15px; border-radius: 5px; margin-bottom: 15px; font-family: monospace; font-size: 12px; color: #333; border: 1px solid #ddd;">
                <div><strong>AIAGENT_NSFW_DEFAULT_AROUSAL:</strong> 20</div>
                <div style="margin-top: 10px; color: #666;">All NPCs under this profile have base arousal of 20, enabling all sex actions. Use Profile Prompt to provide context:</div>
                <div style="margin-top: 10px; background: #fff; padding: 10px; border-radius: 3px; border-left: 3px solid #667eea;">
                    <div>#HERIKA_NAME# is a sex worker. Offers adult entertainment services for gold:</div>
                    <div style="margin-top: 5px; color: #666;">• massage: 50 gold</div>
                    <div>• manual: 100 gold</div>
                    <div>• pectoral job: 150 gold</div>
                    <div>• mouth job: 200 gold</div>
                    <div>• love: 500 gold</div>
                </div>
            </div>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">Roadmap</h3>
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; color: #333;">
                <p><strong>Planned Features:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>Multi-NPC intimate scenes</li>
                    <li>Non-player character scenes</li>
                </ul>
            </div>

            <h3 style="margin: 25px 0 15px; color: #555; font-size: 16px;">Quick Links</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                <button class="btn-secondary" onclick="switchTab('scenes')" style="cursor: pointer;">📋 Go to Scenes Manager</button>
                <button class="btn-secondary" onclick="switchTab('tools')" style="cursor: pointer;">🛠️ Go to Tools</button>
                <button class="btn-secondary" onclick="switchTab('settings')" style="cursor: pointer;">⚙️ Go to Settings</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <h2 style="margin-bottom: 20px; color: #333;">Edit Scene</h2>

            <div class="form-group">
                <label>Stage (ID)</label>
                <input type="text" id="editStage" disabled style="background: #f5f5f5;">
            </div>

            <div class="form-group">
                <label>Description</label>
                <input type="text" id="editDesc">
            </div>

            <div class="form-group">
                <label>Description (Spanish)</label>
                <input type="text" id="editDescEs">
            </div>

            <div class="form-group">
                <label>Description (English)</label>
                <input type="text" id="editDescEn">
            </div>

            <div class="form-group">
                <label>Internal Description</label>
                <textarea id="editIDesc"></textarea>
            </div>

            <div class="button-group">
                <button class="btn-primary" onclick="saveEdit()">💾 Save Changes</button>
                <button class="btn-secondary" onclick="closeEditModal()">❌ Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Pagination variables
        let allScenes = [];
        let currentPage = 1;
        const itemsPerPage = 50;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadScenes();
            loadNPCSelector();
            loadConnectorSelector();
            loadSettings();
        });

        // Tab switching
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Deactivate all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            document.getElementById(tabName).classList.add('active');

            // Activate button
            event.target.classList.add('active');
        }

        // Alert handling
        function showAlert(elementId, message, type) {
            const alertEl = document.getElementById(elementId);
            alertEl.textContent = message;
            alertEl.className = `alert ${type}`;
            alertEl.style.display = 'block';

            setTimeout(() => {
                alertEl.style.display = 'none';
            }, 10000);
        }

        // Load scenes
        function loadScenes() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=read')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('scenesLoading').classList.remove('active');

                    if (data.success) {
                        allScenes = data.data;
                        currentPage = 1;
                        displayScenesPage();
                    } else {
                        showAlert('sceneErrorAlert', 'Error loading scenes: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    document.getElementById('scenesLoading').classList.remove('active');
                    showAlert('sceneErrorAlert', 'Network error: ' + error.message, 'error');
                });
        }

        // Display scenes for current page
        function displayScenesPage() {
            const tbody = document.getElementById('scenesTableBody');
            tbody.innerHTML = '';

            if (allScenes.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999;">No scenes found. Create one to get started!</td></tr>';
                document.getElementById('scenesTable').style.display = 'table';
                document.getElementById('paginationContainer').style.display = 'none';
                return;
            }

            // Calculate pagination
            const totalPages = Math.ceil(allScenes.length / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, allScenes.length);
            const scenesOnPage = allScenes.slice(startIndex, endIndex);

            // Populate table
            scenesOnPage.forEach(scene => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td><strong>${escapeHtml(scene.stage)}</strong></td>
                    <td>${escapeHtml(scene.description || '-')}</td>
                    <td>${escapeHtml(scene.description_es || '-')}</td>
                    <td>${escapeHtml(scene.description_en || '-')}</td>
                    <td>${escapeHtml(scene.i_desc || '-')}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-warning" onclick="editScene('${escapeAttr(scene.stage)}', '${escapeAttr(scene.description || '')}', '${escapeAttr(scene.description_es || '')}', '${escapeAttr(scene.description_en || '')}', '${escapeAttr(scene.i_desc || '')}')">✏️ Edit</button>
                            <button class="btn-danger" onclick="deleteScene('${escapeAttr(scene.stage)}')">🗑️ Delete</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });

            document.getElementById('scenesTable').style.display = 'table';

            // Show pagination controls
            if (totalPages > 1) {
                document.getElementById('paginationContainer').style.display = 'block';
                renderPagination(totalPages);
            } else {
                document.getElementById('paginationContainer').style.display = 'none';
            }

            // Update pagination info
            document.getElementById('paginationInfo').textContent = 
                `Showing ${startIndex + 1}-${endIndex} of ${allScenes.length} scenes (Page ${currentPage}/${totalPages})`;
        }

        // Render pagination controls
        function renderPagination(totalPages) {
            const paginationControls = document.getElementById('paginationControls');
            paginationControls.innerHTML = '';

            // Previous button
            const prevBtn = document.createElement('button');
            prevBtn.textContent = '← Previous';
            prevBtn.disabled = currentPage === 1;
            prevBtn.onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    displayScenesPage();
                }
            };
            paginationControls.appendChild(prevBtn);

            // Page numbers
            const maxVisiblePages = 7;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
            
            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            if (startPage > 1) {
                const firstBtn = document.createElement('button');
                firstBtn.textContent = '1';
                firstBtn.onclick = () => {
                    currentPage = 1;
                    displayScenesPage();
                };
                paginationControls.appendChild(firstBtn);

                if (startPage > 2) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    paginationControls.appendChild(dots);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = i === currentPage ? 'active' : '';
                btn.onclick = () => {
                    currentPage = i;
                    displayScenesPage();
                };
                paginationControls.appendChild(btn);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('span');
                    dots.textContent = '...';
                    paginationControls.appendChild(dots);
                }

                const lastBtn = document.createElement('button');
                lastBtn.textContent = totalPages;
                lastBtn.onclick = () => {
                    currentPage = totalPages;
                    displayScenesPage();
                };
                paginationControls.appendChild(lastBtn);
            }

            // Next button
            const nextBtn = document.createElement('button');
            nextBtn.textContent = 'Next →';
            nextBtn.disabled = currentPage === totalPages;
            nextBtn.onclick = () => {
                if (currentPage < totalPages) {
                    currentPage++;
                    displayScenesPage();
                }
            };
            paginationControls.appendChild(nextBtn);
        }

        // Create scene
        function createScene() {
            const stage = document.getElementById('sceneStage').value.trim();
            const description = document.getElementById('sceneDesc').value.trim();
            const description_es = document.getElementById('sceneDescEs').value.trim();
            const description_en = document.getElementById('sceneDescEn').value.trim();
            const i_desc = document.getElementById('sceneIDesc').value.trim();

            if (!stage) {
                showAlert('sceneErrorAlert', 'Stage/ID is required', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('stage', stage);
            formData.append('description', description);
            formData.append('description_es', description_es);
            formData.append('description_en', description_en);
            formData.append('i_desc', i_desc);

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=create', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('sceneSuccessAlert', data.message, 'success');
                    clearSceneForm();
                    loadScenes();
                } else {
                    showAlert('sceneErrorAlert', 'Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showAlert('sceneErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Edit scene
        function editScene(stage, description, description_es, description_en, i_desc) {
            document.getElementById('editStage').value = stage;
            document.getElementById('editDesc').value = description;
            document.getElementById('editDescEs').value = description_es;
            document.getElementById('editDescEn').value = description_en;
            document.getElementById('editIDesc').value = i_desc;
            document.getElementById('editModal').style.display = 'block';
        }

        // Save edit
        function saveEdit() {
            const stage = document.getElementById('editStage').value;
            const description = document.getElementById('editDesc').value.trim();
            const description_es = document.getElementById('editDescEs').value.trim();
            const description_en = document.getElementById('editDescEn').value.trim();
            const i_desc = document.getElementById('editIDesc').value.trim();

            const formData = new FormData();
            formData.append('stage', stage);
            formData.append('description', description);
            formData.append('description_es', description_es);
            formData.append('description_en', description_en);
            formData.append('i_desc', i_desc);

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('sceneSuccessAlert', data.message, 'success');
                    closeEditModal();
                    loadScenes();
                } else {
                    showAlert('sceneErrorAlert', 'Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showAlert('sceneErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Delete scene
        function deleteScene(stage) {
            if (!confirm('Are you sure you want to delete this scene?')) {
                return;
            }

            const formData = new FormData();
            formData.append('stage', stage);

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=delete', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('sceneSuccessAlert', data.message, 'success');
                    loadScenes();
                } else {
                    showAlert('sceneErrorAlert', 'Error: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showAlert('sceneErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Clear form
        function clearSceneForm() {
            document.getElementById('sceneStage').value = '';
            document.getElementById('sceneDesc').value = '';
            document.getElementById('sceneDescEs').value = '';
            document.getElementById('sceneDescEn').value = '';
            document.getElementById('sceneIDesc').value = '';
        }

        // Generate Scene Descriptions
        function generateSceneDescriptions() {
            if (!confirm('Generate descriptions from internal descriptions? This will make a request to the server.')) {
                return;
            }

            showProcessing();
            fetch('<?php echo dirname($_SERVER['PHP_SELF']); ?>/cmd/gen_scene_desc.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                hideProcessing();
                if (data.success) {
                    showAlert('sceneSuccessAlert', 'Descriptions generated successfully', 'success');
                    // Reload the page after a short delay
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('sceneErrorAlert', 'Error: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                hideProcessing();
                showAlert('sceneErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Import Scenes from File
        function importScenes() {
            const fileInput = document.getElementById('importFile');
            if (!fileInput.files || fileInput.files.length === 0) {
                showAlert('sceneErrorAlert', 'Please select a file to import', 'error');
                return;
            }

            if (!confirm('Import scenes from file? Duplicate scenes will be updated.')) {
                return;
            }

            const formData = new FormData();
            formData.append('importFile', fileInput.files[0]);

            showProcessing();
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=importData', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideProcessing();
                if (data.success) {
                    showAlert('sceneSuccessAlert', data.message, 'success');
                    fileInput.value = ''; // Clear file input
                    loadScenes();
                } else {
                    showAlert('sceneErrorAlert', 'Error: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                hideProcessing();
                showAlert('sceneErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Utility functions
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, m => map[m]);
        }

        function escapeAttr(text) {
            if (!text) return '';
            return text.toString().replace(/'/g, "\\'").replace(/"/g, '&quot;');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        });

        // ==================== TOOLS TAB FUNCTIONS ====================

        // Load NPC Selector
        function loadNPCSelector() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=loadNPCs')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const dropdown = document.getElementById('npcSelectDropdown');
                        dropdown.innerHTML = '';

                        if (data.data.length === 0) {
                            dropdown.innerHTML = '<div class="searchable-select-option">No NPCs found</div>';
                        } else {
                            window.npcListData = data.data; // Store for searching
                            data.data.forEach(npc => {
                                const option = document.createElement('div');
                                option.className = 'searchable-select-option';
                                option.innerHTML = escapeHtml(npc.npc_name);
                                option.dataset.id = npc.id;
                                option.dataset.name = npc.npc_name;
                                option.onclick = function() {
                                    selectNPC(npc.id, npc.npc_name);
                                };
                                dropdown.appendChild(option);
                            });
                        }
                    } else {
                        showAlert('toolsErrorAlert', 'Error loading NPCs: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showAlert('toolsErrorAlert', 'Network error: ' + error.message, 'error');
                });
        }

        // Load Connector Selector
        function loadConnectorSelector() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=loadConnectors')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const dropdown = document.getElementById('connectorSelectDropdown');
                        dropdown.innerHTML = '';

                        if (data.data.length === 0) {
                            dropdown.innerHTML = '<div class="searchable-select-option">No Connectors found</div>';
                        } else {
                            window.connectorListData = data.data; // Store for searching
                            data.data.forEach(connector => {
                                const option = document.createElement('div');
                                option.className = 'searchable-select-option';
                                option.innerHTML = escapeHtml(connector.label);
                                option.dataset.id = connector.id;
                                option.dataset.label = connector.label;
                                option.onclick = function() {
                                    selectConnector(connector.id, connector.label);
                                };
                                dropdown.appendChild(option);
                            });
                        }
                    } else {
                        showAlert('toolsErrorAlert', 'Error loading Connectors: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showAlert('toolsErrorAlert', 'Network error: ' + error.message, 'error');
                });
        }

        // NPC Search Handler
        document.addEventListener('DOMContentLoaded', function() {
            const npcInput = document.getElementById('npcSelectInput');
            const npcDropdown = document.getElementById('npcSelectDropdown');

            if (npcInput) {
                npcInput.addEventListener('focus', function() {
                    npcDropdown.classList.add('active');
                });

                npcInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const options = npcDropdown.querySelectorAll('.searchable-select-option');

                    options.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        option.style.display = text.includes(searchTerm) ? 'block' : 'none';
                    });

                    if (searchTerm.length > 0) {
                        npcDropdown.classList.add('active');
                    } else {
                        npcDropdown.classList.add('active');
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.searchable-select-wrapper')) {
                        npcDropdown.classList.remove('active');
                    }
                });
            }
        });

        // Select NPC
        function selectNPC(npcId, npcName) {
            document.getElementById('npcSelectInput').value = npcName;
            document.getElementById('npcSelectValue').value = npcId;
            document.getElementById('npcSelectDropdown').classList.remove('active');
        }

        // Select Connector
        function selectConnector(connectorId, connectorLabel) {
            document.getElementById('connectorSelectInput').value = connectorLabel;
            document.getElementById('connectorSelectValue').value = connectorId;
            document.getElementById('connectorSelectDropdown').classList.remove('active');
        }

        // Connector Search Handler
        document.addEventListener('DOMContentLoaded', function() {
            const connectorInput = document.getElementById('connectorSelectInput');
            const connectorDropdown = document.getElementById('connectorSelectDropdown');

            if (connectorInput) {
                connectorInput.addEventListener('focus', function() {
                    connectorDropdown.classList.add('active');
                });

                connectorInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const options = connectorDropdown.querySelectorAll('.searchable-select-option');

                    options.forEach(option => {
                        const text = option.textContent.toLowerCase();
                        option.style.display = text.includes(searchTerm) ? 'block' : 'none';
                    });

                    if (searchTerm.length > 0) {
                        connectorDropdown.classList.add('active');
                    } else {
                        connectorDropdown.classList.add('active');
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.searchable-select-wrapper')) {
                        connectorDropdown.classList.remove('active');
                    }
                });
            }
        });

        // Generate Prompt
        function generatePrompt(fieldType) {
            const npcId = document.getElementById('npcSelectValue').value;
            const connectorId = document.getElementById('connectorSelectValue').value;
            const profanityLevel = document.getElementById('profanityLevel').value;

            if (!npcId) {
                showAlert('toolsErrorAlert', 'Please select an NPC first', 'error');
                return;
            }

            if (!connectorId) {
                showAlert('toolsErrorAlert', 'Please select a Connector first', 'error');
                return;
            }

            if (!profanityLevel) {
                showAlert('toolsErrorAlert', 'Please select a Profanity Level', 'error');
                return;
            }

            // Fetch to cmd/gen_prompt.php with the selected NPC, connector, profanity level, and field type
            const formData = new FormData();
            formData.append('npc_id', npcId);
            formData.append('connector_id', connectorId);
            formData.append('profanity_level', profanityLevel);
            formData.append('field_type', fieldType);
            // Coding Agent: Do not touch URL. is ok as is.
            showProcessing()
            fetch('<?php echo dirname($_SERVER['PHP_SELF']); ?>/cmd/gen_prompt.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideProcessing();
                if (data.success) {
                    // Update the appropriate textarea with generated content
                    if (fieldType === 'sex_prompt') {
                        document.getElementById('sexPrompt').value = data.prompt || '';
                    } else if (fieldType === 'sex_speech_style') {
                        document.getElementById('sexSpeechStyle').value = data.prompt || '';
                    }
                    showAlert('toolsSuccessAlert', 'Prompt generated successfully', 'success');
                } else {
                    showAlert('toolsErrorAlert', 'Error generating prompt: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                hideProcessing();
                showAlert('toolsErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Submit Tools Form
        function submitToolsForm() {
            const npcId = document.getElementById('npcSelectValue').value;
            const connectorId = document.getElementById('connectorSelectValue').value;
            const profanityLevel = document.getElementById('profanityLevel').value;
            const sexPrompt = document.getElementById('sexPrompt').value.trim();
            const sexSpeechStyle = document.getElementById('sexSpeechStyle').value.trim();

            if (!npcId) {
                showAlert('toolsErrorAlert', 'Please select an NPC', 'error');
                return;
            }

            /* This fields are not needed when submitting form, we only need npc_id, sex_prompt and sex_speech_style
            if (!connectorId) {
                showAlert('toolsErrorAlert', 'Please select a Connector', 'error');
                return;
            }

            if (!profanityLevel) {
                showAlert('toolsErrorAlert', 'Please select a Profanity Level', 'error');
                return;
            }
            */
            const formData = new FormData();
            formData.append('npc_id', npcId);
            formData.append('connector_id', connectorId);
            formData.append('profanity_level', profanityLevel);
            formData.append('sex_prompt', sexPrompt);
            formData.append('sex_speech_style', sexSpeechStyle);

            showProcessing();
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=submitToolsForm', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideProcessing();
                if (data.success) {
                    showAlert('toolsSuccessAlert', data.message || 'Form submitted successfully', 'success');
                    clearToolsForm();
                } else {
                    showAlert('toolsErrorAlert', 'Error: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                hideProcessing();
                showAlert('toolsErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Clear Tools Form
       function clearToolsForm()
        {
            document . getElementById('npcSelectInput') . value       = '';
            document . getElementById('npcSelectValue') . value       = '';
            document . getElementById('connectorSelectInput') . value = '';
            document . getElementById('connectorSelectValue') . value = '';
            document . getElementById('sexPrompt') . value            = '';
            document . getElementById('sexSpeechStyle') . value       = '';
        }

        // ==================== SETTINGS TAB FUNCTIONS ====================

        // Load Settings
        function loadSettings() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=loadSettings')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('xttsModifyLevel1').checked = data.data.XTTS_MODIFY_LEVEL1 || false;
                        document.getElementById('xttsModifyLevel2').checked = data.data.XTTS_MODIFY_LEVEL2 || false;
                        document.getElementById('trackDrunkStatus').checked = data.data.TRACK_DRUNK_STATUS || false;
                        document.getElementById('trackFertilityInfo').checked = data.data.TRACK_FERTILITY_INFO || false;
                        document.getElementById('genericGlossary').value = data.data.GENERIC_GLOSSARY || '';
                    } else {
                        showAlert('settingsErrorAlert', 'Error loading settings: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showAlert('settingsErrorAlert', 'Network error: ' + error.message, 'error');
                });
        }

        // Save Settings
        function saveSettings() {
            const formData = new FormData();
            formData.append('XTTS_MODIFY_LEVEL1', document.getElementById('xttsModifyLevel1').checked);
            formData.append('XTTS_MODIFY_LEVEL2', document.getElementById('xttsModifyLevel2').checked);
            formData.append('TRACK_DRUNK_STATUS', document.getElementById('trackDrunkStatus').checked);
            formData.append('TRACK_FERTILITY_INFO', document.getElementById('trackFertilityInfo').checked);
            formData.append('GENERIC_GLOSSARY', document.getElementById('genericGlossary').value.trim());

            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=saveSettings', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('settingsSuccessAlert', data.message, 'success');
                } else {
                    showAlert('settingsErrorAlert', 'Error saving settings: ' + data.error, 'error');
                }
            })
            .catch(error => {
                showAlert('settingsErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }

        // Reset Settings
        function resetSettings() {
            loadSettings();
        }

        // Generate Table
        function generateTable() {
            if (!confirm('Create the ext_aiagentnsfw_scenes table? This will set up the database table for storing scene data.')) {
                return;
            }

            showProcessing();
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>?action=generateTable', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                hideProcessing();
                if (data.success) {
                    showAlert('settingsSuccessAlert', data.message, 'success');
                } else {
                    showAlert('settingsErrorAlert', 'Error: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                hideProcessing();
                showAlert('settingsErrorAlert', 'Network error: ' + error.message, 'error');
            });
        }
        function showProcessing(){

            processingMessage                           = document . createElement('div');
            processingMessage . textContent             = 'Processing...';
            processingMessage . style . position        = 'fixed';
            processingMessage . style . top             = '50%';
            processingMessage . style . left            = '50%';
            processingMessage . style . transform       = 'translate(-50%, -50%)';
            processingMessage . style . backgroundColor = '#000';
            processingMessage . style . color           = '#fff';
            processingMessage . style . padding         = '10px 20px';
            processingMessage . style . borderRadius    = '8px';
            processingMessage . style . zIndex          = '10001';
            processingMessage . id                      = "processing_wheel";
            document . body . appendChild(processingMessage);
        }
        function hideProcessing()
        {
            processingMessage . innerHTML      = '';
            processingMessage . style . zIndex = '-10001';

        }

    var processingMessage;
    </script>
</body>
</html>
