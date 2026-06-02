<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/nova1/app/helpers/crypto.php';

class MoodleService
{
    private static $categoriesCache = [];
    private static $moodleCoursesCache = [];


    public static function getConfiguration($conn, $institutionId, $groupId)
    {
        $stmt = $conn->prepare("
            SELECT moodle_url, api_token
            FROM moodle_configurations
            WHERE institution_id = ?
              AND group_id = ?
            LIMIT 1
        ");

        $stmt->bind_param("ii", $institutionId, $groupId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            return [
                'url'   => rtrim($row['moodle_url'], '/'),
                'token' => decryptValue($row['api_token'])
            ];
        }

        return null;
    }

    public static function normalizeText($text)
    {
        $text = mb_strtolower(trim((string)$text), 'UTF-8');

        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $text
        );

        $text = preg_replace('/[^a-z0-9 ]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public static function getCategories($moodleUrl, $token)
    {
        $cacheKey = md5($moodleUrl . '|' . $token);

        if (isset(self::$categoriesCache[$cacheKey])) {
            return self::$categoriesCache[$cacheKey];
        }

        $url = $moodleUrl . '/webservice/rest/server.php'
            . '?wstoken=' . urlencode($token)
            . '&wsfunction=core_course_get_categories'
            . '&moodlewsrestformat=json';

        $response = false;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $response = @file_get_contents($url);
        }

        if (!$response) {
            self::$categoriesCache[$cacheKey] = [];
            return [];
        }

        $data = json_decode($response, true);

        if (!is_array($data) || isset($data['exception'])) {
            self::$categoriesCache[$cacheKey] = [];
            return [];
        }

        self::$categoriesCache[$cacheKey] = $data;

        return $data;
    }

    public static function findCategoryByName($categories, $searchName, $parentId = null)
    {
        $search = self::normalizeText($searchName);

        foreach ($categories as $category) {

            if (!isset($category['name'])) {
                continue;
            }

            if ($parentId !== null) {
                $currentParent = (int)($category['parent'] ?? 0);

                if ($currentParent !== (int)$parentId) {
                    continue;
                }
            }

            $moodleName = self::normalizeText($category['name']);

            if (
                $moodleName === $search ||
                strpos($moodleName, $search) !== false ||
                strpos($search, $moodleName) !== false
            ) {
                return $category;
            }
        }

        return null;
    }

    public static function periodExistsInMoodle($conn, $institutionId, $groupId, $periodName)
    {
        $config = self::getConfiguration($conn, $institutionId, $groupId);

        if (!$config) {
            return [
                'exists' => false,
                'message' => 'Sin conexión Moodle'
            ];
        }

        $categories = self::getCategories($config['url'], $config['token']);

        if (empty($categories)) {
            return [
                'exists' => false,
                'message' => 'Sin categorías'
            ];
        }

        $periodCategory = self::findCategoryByName($categories, $periodName);

        if ($periodCategory) {
            return [
                'exists' => true,
                'message' => 'En Moodle',
                'moodle_category_id' => $periodCategory['id'],
                'moodle_category_name' => $periodCategory['name']
            ];
        }

        return [
            'exists' => false,
            'message' => 'No existe'
        ];
    }

    public static function courseExistsInMoodle($conn, $institutionId, $groupId, $periodName, $courseName)
    {
        $config = self::getConfiguration($conn, $institutionId, $groupId);

        if (!$config) {
            return [
                'exists' => false,
                'message' => 'Sin conexión Moodle'
            ];
        }

        $categories = self::getCategories($config['url'], $config['token']);

        if (empty($categories)) {
            return [
                'exists' => false,
                'message' => 'Sin categorías'
            ];
        }

        $periodCategory = self::findCategoryByName($categories, $periodName);

        if (!$periodCategory) {
            return [
                'exists' => false,
                'message' => 'Período no existe'
            ];
        }

        $courseCategory = self::findCategoryByName(
            $categories,
            $courseName,
            $periodCategory['id']
        );

        if ($courseCategory) {
            return [
                'exists' => true,
                'message' => 'En Moodle',
                'moodle_course_category' => $courseCategory['name']
            ];
        }

        return [
            'exists' => false,
            'message' => 'No existe'
        ];
    }

    public static function checkApiConnection($moodleUrl, $token)
    {
        $url = rtrim($moodleUrl, '/') . '/webservice/rest/server.php'
            . '?wstoken=' . urlencode($token)
            . '&wsfunction=core_webservice_get_site_info'
            . '&moodlewsrestformat=json';

        $response = false;
        $httpCode = 0;
        $curlError = '';

        if (function_exists('curl_init')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);
        }

        if ($response === false) {
            if ($curlError !== '') {
                return ['ok' => false, 'message' => 'Dominio inaccesible', 'detail' => $curlError];
            }
            return ['ok' => false, 'message' => 'Servidor no responde', 'detail' => 'No hubo respuesta'];
        }

        if ($httpCode === 403) return ['ok' => false, 'message' => 'Acceso bloqueado', 'detail' => 'Servidor rechazó conexión'];
        if ($httpCode === 404) return ['ok' => false, 'message' => 'URL Moodle incorrecta', 'detail' => 'Endpoint no encontrado'];
        if ($httpCode === 500) return ['ok' => false, 'message' => 'Moodle con error interno', 'detail' => 'Error interno Moodle'];
        if ($httpCode !== 200) return ['ok' => false, 'message' => 'Respuesta inesperada', 'detail' => 'HTTP ' . $httpCode];

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Respuesta inválida', 'detail' => 'No devolvió JSON'];
        }

        if (isset($data['exception'])) {

            $exception = strtolower($data['exception']);
            $messageRaw = strtolower($data['message'] ?? '');

            if (strpos($exception, 'invalidtoken') !== false) {
                return ['ok' => false, 'message' => 'Token inválido', 'detail' => 'Token API no funcional'];
            }

            if (
                strpos($exception, 'accessexception') !== false ||
                strpos($messageRaw, 'access control exception') !== false
            ) {
                return ['ok' => false, 'message' => 'Token sin permisos', 'detail' => 'Sin permisos webservice'];
            }

            if (strpos($messageRaw, 'web service is not available') !== false) {
                return ['ok' => false, 'message' => 'Servicios web deshabilitados', 'detail' => 'Webservices apagados'];
            }

            return ['ok' => false, 'message' => 'Error de integración', 'detail' => $data['message'] ?? ''];
        }

        return [
            'ok' => true,
            'message' => 'Conectado',
            'detail' => ($data['sitename'] ?? 'Moodle') . ' | Usuario: ' . ($data['username'] ?? 'N/A')
        ];
    }

    public static function getApiStatusByConfiguration($conn, $institutionId, $groupId)
    {
        $config = self::getConfiguration($conn, $institutionId, $groupId);

        if (!$config) {
            return ['ok' => false, 'message' => 'Sin configuración'];
        }

        return self::checkApiConnection($config['url'], $config['token']);
    }

    public static function getMoodleCourses($moodleUrl, $token)
    {
        $cacheKey = md5('courses|' . $moodleUrl . '|' . $token);

        if (isset(self::$moodleCoursesCache[$cacheKey])) {
            return self::$moodleCoursesCache[$cacheKey];
        }

        $url = $moodleUrl . '/webservice/rest/server.php'
            . '?wstoken=' . urlencode($token)
            . '&wsfunction=core_course_get_courses'
            . '&moodlewsrestformat=json';

        $response = false;

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
        } else {
            $response = @file_get_contents($url);
        }

        if (!$response) {
            self::$moodleCoursesCache[$cacheKey] = [];
            return [];
        }

        $data = json_decode($response, true);

        if (!is_array($data) || isset($data['exception'])) {
            self::$moodleCoursesCache[$cacheKey] = [];
            return [];
        }

        self::$moodleCoursesCache[$cacheKey] = $data;

        return $data;
    }

    public static function subjectExistsInMoodle($conn, $institutionId, $groupId, $periodName, $courseName, $subjectCode)
    {
        $config = self::getConfiguration($conn, $institutionId, $groupId);

        if (!$config) {
            return [
                'exists' => false,
                'message' => 'Sin conexión Moodle'
            ];
        }

        $categories = self::getCategories($config['url'], $config['token']);

        if (empty($categories)) {
            return [
                'exists' => false,
                'message' => 'Sin categorías'
            ];
        }

        $periodCategory = self::findCategoryByName($categories, $periodName);

        if (!$periodCategory) {
            return [
                'exists' => false,
                'message' => 'Período no existe'
            ];
        }

        $courseCategory = self::findCategoryByName(
            $categories,
            $courseName,
            $periodCategory['id']
        );

        if (!$courseCategory) {
            return [
                'exists' => false,
                'message' => 'Curso no existe'
            ];
        }

        $moodleCourses = self::getMoodleCourses($config['url'], $config['token']);

        if (empty($moodleCourses)) {
            return [
                'exists' => false,
                'message' => 'Sin cursos Moodle'
            ];
        }

        $searchShortname = self::normalizeText($subjectCode);

        foreach ($moodleCourses as $mCourse) {

            $categoryId = (int)($mCourse['categoryid'] ?? 0);
            $shortname  = self::normalizeText($mCourse['shortname'] ?? '');

            if (
                $categoryId === (int)$courseCategory['id'] &&
                $shortname === $searchShortname
            ) {
                return [
                    'exists' => true,
                    'message' => 'En Moodle',
                    'moodle_course_name' => $mCourse['fullname'] ?? ''
                ];
            }
        }

        return [
            'exists' => false,
            'message' => 'No existe'
        ];
    }

    public static function getGradesBySubject($conn, $institutionId, $groupId, $periodName, $courseName, $subjectCode)
    {
        $config = self::getConfiguration($conn, $institutionId, $groupId);

        if (!$config) {
            return [
                'ok' => false,
                'message' => 'Sin conexión Moodle'
            ];
        }

        $categories = self::getCategories($config['url'], $config['token']);

        $periodCategory = self::findCategoryByName($categories, $periodName);

        if (!$periodCategory) {
            return [
                'ok' => false,
                'message' => 'Período no existe en Moodle'
            ];
        }

        $courseCategory = self::findCategoryByName($categories, $courseName, $periodCategory['id']);

        if (!$courseCategory) {
            return [
                'ok' => false,
                'message' => 'Curso no existe en Moodle'
            ];
        }

        $moodleCourses = self::getMoodleCourses($config['url'], $config['token']);

        $targetCourse = null;
        $searchShortname = self::normalizeText($subjectCode);

        foreach ($moodleCourses as $mCourse) {

            $categoryId = (int)($mCourse['categoryid'] ?? 0);
            $shortname = self::normalizeText($mCourse['shortname'] ?? '');

            if (
                $categoryId === (int)$courseCategory['id'] &&
                $shortname === $searchShortname
            ) {
                $targetCourse = $mCourse;
                break;
            }
        }

        if (!$targetCourse) {
            return [
                'ok' => false,
                'message' => 'Materia no existe en Moodle'
            ];
        }

        /*
    |--------------------------------------------------------------------------
    | OBTENER USUARIOS MATRICULADOS
    |--------------------------------------------------------------------------
    */
        $usersUrl = $config['url'] . '/webservice/rest/server.php'
            . '?wstoken=' . urlencode($config['token'])
            . '&wsfunction=core_enrol_get_enrolled_users'
            . '&courseid=' . $targetCourse['id']
            . '&moodlewsrestformat=json';

        $usersResponse = @file_get_contents($usersUrl);

        if (!$usersResponse) {
            return [
                'ok' => false,
                'message' => 'No se pudo obtener matriculados'
            ];
        }

        $usersData = json_decode($usersResponse, true);

        if (!is_array($usersData) || isset($usersData['exception'])) {
            return [
                'ok' => false,
                'message' => 'Error al consultar matriculados'
            ];
        }

        $studentsOnly = [];
        $teachers = [];

        foreach ($usersData as $user) {

            $roleNames = [];

            if (isset($user['roles']) && is_array($user['roles'])) {
                foreach ($user['roles'] as $role) {
                    $roleNames[] = strtolower($role['shortname'] ?? $role['name'] ?? '');
                }
            }

            $joinedRoles = implode('|', $roleNames);

            /*
            |--------------------------------------------------------------------------
            | ESTUDIANTES
            |--------------------------------------------------------------------------
            */
            if (
                strpos($joinedRoles, 'student') !== false ||
                strpos($joinedRoles, 'estudiante') !== false ||
                strpos($joinedRoles, 'alumno') !== false
            ) {
                $studentsOnly[] = $user;
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | DOCENTES
            |--------------------------------------------------------------------------
            */
            if (
                strpos($joinedRoles, 'teacher') !== false ||
                strpos($joinedRoles, 'editingteacher') !== false ||
                strpos($joinedRoles, 'profesor') !== false
            ) {
                $teachers[] = $user['fullname'] ?? 'Docente';
                continue;
            }


        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTAR NOTAS EN PARALELO (MULTICURL)
        |--------------------------------------------------------------------------
        */

        if (!function_exists('curl_multi_init')) {
            return [
                'ok' => false,
                'message' => 'Servidor PHP no soporta curl_multi'
            ];
        }

        $students = [];
        $gradeHeaders = [];
        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach ($studentsOnly as $index => $user) {

            $gradeUrl = $config['url'] . '/webservice/rest/server.php'
                . '?wstoken=' . urlencode($config['token'])
                . '&wsfunction=gradereport_user_get_grade_items'
                . '&courseid=' . $targetCourse['id']
                . '&userid=' . $user['id']
                . '&moodlewsrestformat=json';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $gradeUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_multi_add_handle($multiHandle, $ch);

            $curlHandles[$index] = [
                'handle' => $ch,
                'user' => $user
            ];
        }

        $running = null;

        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle);
        } while ($running > 0);

        /*
|--------------------------------------------------------------------------
| PROCESAR RESPUESTAS
|--------------------------------------------------------------------------
*/
        foreach ($curlHandles as $item) {

            $user = $item['user'];
            $ch = $item['handle'];

            $gradeResponse = curl_multi_getcontent($ch);
            $gradeData = json_decode($gradeResponse, true);

            $studentRow = [
                'student_name' => $user['fullname'] ?? 'Estudiante',
                'grades' => [],
                'final' => '-'
            ];

            if (isset($gradeData['usergrades'][0]['gradeitems'])) {

                foreach ($gradeData['usergrades'][0]['gradeitems'] as $gradeItem) {

                    $itemName = trim($gradeItem['itemname'] ?? '');

                    if ($itemName === '') {
                        continue;
                    }

                    $gradeValue = $gradeItem['graderaw'] ?? $gradeItem['gradeformatted'] ?? '-';

                    if (($gradeItem['itemtype'] ?? '') === 'course') {
                        $studentRow['final'] = $gradeValue;
                        continue;
                    }

                    $studentRow['grades'][$itemName] = $gradeValue;

                    if (!in_array($itemName, $gradeHeaders, true)) {
                        $gradeHeaders[] = $itemName;
                    }
                }
            }

            $students[] = $studentRow;

            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);
        }

        curl_multi_close($multiHandle);

        return [
            'ok' => true,
            'message' => 'Calificaciones cargadas',
            'headers' => $gradeHeaders,
            'students' => $students,
            'teachers' => $teachers,
        ];
    }
}
