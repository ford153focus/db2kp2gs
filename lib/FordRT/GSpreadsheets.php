<?php
/**
 * User: focus
 * Date: 04.10.18
 * Time: 17:31
 *
 * @noinspection PhpVariableNamingConventionInspection
 */

declare(strict_types=1);

namespace FordRT;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_Spreadsheet;

/**
 * Class GSpreadsheets
 * @property Google_Client client
 * @property Google_Service_Sheets service
 * @property Google_Service_Sheets_Spreadsheet createResponse
 * @property string spreadsheet_id
 * @package FordRT
 */
class GSpreadsheets
{
    public function __construct()
    {
        try {
            $this->client = self::getClient();
        } catch (\Exception $exception) {
            var_dump($exception);
            die();
        }

        $this->service = new \Google_Service_Sheets($this->client);

        $this->create();
        $this->save();
        $this->format();
    }

    private function create()
    {
        $request_body = new \Google_Service_Sheets_Spreadsheet();
        $this->createResponse = $this->service->spreadsheets->create($request_body);
        $this->spreadsheet_id = $this->createResponse->getSpreadsheetId();
    }

    /**
     * via https://developers.google.com/sheets/api/guides/batchupdate
     */
    private function format()
    {
        $first_sheet_id = $this->service->spreadsheets->get($this->spreadsheet_id)->getSheets()[0]->properties->sheetId;
        $requests = [];

        // Change the spreadsheet's title.
        $requests[] = new \Google_Service_Sheets_Request([
            'updateSpreadsheetProperties' => [
                'properties' => [
                    'title' => "Lum pw database backup for " . date("Y-m-d H-i-s")
                ],
                'fields' => 'title'
            ]
        ]);

        $requests[] = new \Google_Service_Sheets_Request([
            'repeatCell' => [
                'range' => [
                    'sheetId' => $first_sheet_id,
                    'startRowIndex' => 0,
                    'endRowIndex' => 1,
                ],
                'cell' => [
                    'userEnteredFormat' => [
                        'backgroundColor' => [
                            'red' => 0.0,
                            'green' => 0.0,
                            'blue' => 0.0
                        ],
                        'horizontalAlignment' => 'CENTER',
                        'textFormat' => [
                            'foregroundColor' => [
                                'red' => 1.0,
                                'green' => 1.0,
                                'blue' => 1.0
                            ],
                            'fontSize' => 12,
                            'bold' => true
                        ]
                    ],
                ],
                'fields' => 'userEnteredFormat(backgroundColor,textFormat,horizontalAlignment)'
            ]
        ]);

        $requests[] = new \Google_Service_Sheets_Request([
            'updateSheetProperties' => [
                'properties' => [
                    'gridProperties' => [
                        'frozenRowCount' => 1
                    ]
                ],
                'fields' => 'gridProperties.frozenRowCount'
            ]
        ]);

        $requests[] = new \Google_Service_Sheets_Request([
            'autoResizeDimensions' => [
                'dimensions' => [
                    'sheetId' => $first_sheet_id,
                    'dimension' => 'COLUMNS',
                    'startIndex' => 0,
                    'endIndex' => count($GLOBALS['parsedKp'][0]) - 1,
                ],
            ],
        ]);

        $requests[] = new \Google_Service_Sheets_Request([
            'autoResizeDimensions' => [
                'dimensions' => [
                    'sheetId' => $first_sheet_id,
                    'dimension' => 'ROWS',
                    'startIndex' => 0,
                    'endIndex' => count($GLOBALS['parsedKp']) - 1,
                ],
            ],
        ]);

        $requests[] = new \Google_Service_Sheets_Request([
            'sortRange' => [
                'range' => [
                    'sheetId' => $first_sheet_id,
                    'startRowIndex' => 1,
                    'endRowIndex' => count($GLOBALS['parsedKp']) - 1,
                    'startColumnIndex' => 0,
                    'endColumnIndex' => count($GLOBALS['parsedKp'][0]) - 1,
                ],
                'sortSpecs' => [
                    [
                        "dimensionIndex" => 0,
                        "sortOrder" => "ASCENDING"
                    ],
                    [
                        "dimensionIndex" => 1,
                        "sortOrder" => "ASCENDING"
                    ],
                    [
                        "dimensionIndex" => 2,
                        "sortOrder" => "ASCENDING"
                    ],
                    [
                        "dimensionIndex" => 4,
                        "sortOrder" => "ASCENDING"
                    ]
                ],
            ],
        ]);

        $batch_update_request = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);

        $this->service->spreadsheets->batchUpdate($this->spreadsheet_id, $batch_update_request);
    }

    private function save()
    {
        $row_num = 1;
        foreach ($GLOBALS['parsedKp'] as $kp_row) {
            $column_num = 'A';
            foreach ($kp_row as $kp_cell) {
                $this->updateCell($column_num . $row_num, $kp_cell);
                $column_num++;
            }
            $row_num++;
        }
    }

    /**
     * @param string $range
     * @param string $value
     * via https://developers.google.com/sheets/api/reference/rest/v4/spreadsheets.values/append
     */
    private function updateCell(string $range, string $value)
    {

        // The ID of the spreadsheet to update.
        $spreadsheet_id = $this->createResponse->getSpreadsheetId();

        // Assign values to desired properties of `requestBody`.
        // All existing properties will be replaced
        $request_body = new \Google_Service_Sheets_ValueRange([
            'values' => [
                [
                    $value
                ]
            ]
        ]);

        $options = [
            'valueInputOption' => 'RAW'
        ];

//        $response = $this->service->spreadsheets_values->update($spreadsheet_id, $range, $request_body, $options);
        $response = $this->service->spreadsheets_values->append($spreadsheet_id, $range, $request_body, $options);
        var_dump($response);

        /**
         * This version of the Google Sheets API has a limit of
         * 500 requests per 100 seconds per project, and
         * 100 requests per 100 seconds per user.
         */
        usleep(1000153);
    }

    /**
     * @return Google_Client
     * @throws \Exception
     */
    private static function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName('db2kp2gs');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        try {
            $client->setAuthConfig(PROJECT_ROOT . '/cfg/credentials.json');
        } catch (\Google_Exception $exception) {
            var_dump($exception);
            die();
        }
//        $client->setAuthConfig(PROJECT_ROOT.'/cfg/db2kp2gs-f0bf1a07a8e3.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        $token_path = PROJECT_ROOT . '/cfg/token.json';
        if (file_exists($token_path)) {
            $access_token = json_decode(file_get_contents($token_path), true);
            $client->setAccessToken($access_token);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $auth_url = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $auth_url);
                print 'Enter verification code: ';
                $auth_code = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $access_token = $client->fetchAccessTokenWithAuthCode($auth_code);
                $client->setAccessToken($access_token);

                // Check to see if there was an error.
                if (array_key_exists('error', $access_token)) {
                    throw new \Exception(join(', ', $access_token));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($token_path))) {
                mkdir(dirname($token_path), 0700, true);
            }
            file_put_contents($token_path, json_encode($client->getAccessToken()));
        }
        return $client;
    }
}