<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\SedApiService;

/**
 * SED API Facade
 * 
 * This facade provides a convenient static interface to the SedApiService.
 * 
 * @method static array authenticate()
 * @method static string getToken()
 * @method static bool isTokenValid()
 * @method static void clearToken()
 * @method static array get(string $endpoint, array $params = [])
 * @method static array post(string $endpoint, array $data = [])
 * @method static array put(string $endpoint, array $data = [])
 * @method static array delete(string $endpoint, array $params = [])
 * @method static array request(string $method, string $endpoint, array $options = [])
 * @method static \Illuminate\Http\Client\Response getRawResponse(string $method, string $endpoint, array $options = [])
 * @method static array getStudents(array $filters = [])
 * @method static array getStudent(string $studentId)
 * @method static array createStudent(array $studentData)
 * @method static array updateStudent(string $studentId, array $studentData)
 * @method static array getSchools(array $filters = [])
 * @method static array getSchool(string $schoolId)
 * @method static array getEnrollments(array $filters = [])
 * @method static array getEnrollment(string $enrollmentId)
 * @method static array createEnrollment(array $enrollmentData)
 * @method static array updateEnrollment(string $enrollmentId, array $enrollmentData)
 * 
 * @see \App\Services\SedApiService
 */
class SedApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return SedApiService::class;
    }
}