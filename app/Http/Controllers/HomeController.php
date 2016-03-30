<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Calendar;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    private $code;
    private $client;

    public function __construct()
    {
        $this->middleware('auth');
        $this->code = isset($_GET['code']) ? $_GET['code'] : NULL;
        $this->client = new Google_Client();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    /*
     * auth flow
     */

    public function g()
    {
        $this->client->setAuthConfigFile(config('services.google.secret'));
        if (Session::get('access_token')){
            $this->client->setAccessToken(Session::get('access_token'));
            if($this->client->isAccessTokenExpired()) {
                $authUrl = $this->client->createAuthUrl();
                return Redirect::to($authUrl);
            }
            return Redirect::to('/calendar');
        } else {
            return Redirect::to('/transfer');
        }
        return view('home');
    }

    /*
     * callback service. handle token
     */

    public function t(){
        $this->client->setAuthConfigFile(config('services.google.secret'));
        $this->client->setRedirectUri( 'http://' .$_SERVER['HTTP_HOST'].'/transfer');
        $this->client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
        if (!$this->code){
            $authUrl = $this->client->createAuthUrl();
            return Redirect::to($authUrl);
        } else {
            $this->client->authenticate($this->code);
            Session::set('access_token', $this->client->getAccessToken());
            return Redirect::to('/calendar');
        }
    }

    /*
     * show events
     */

    public function c(){
        $service = new Google_Service_Calendar($this->client);
        if(!Session::get('access_token')){
            return Redirect::to('/g');
        }
        $this->client->setAccessToken(Session::get('access_token'));
        if($this->client->isAccessTokenExpired()) {
            return Redirect::to('/g');
        }
        $service->calendarList->listCalendarList();
        $calendarId = 'primary';
        $params = array(
            'maxResults' => 20,
            'singleEvents' => TRUE,
            'timeMin' => date('c'),
        );
        $results = $service->events->listEvents($calendarId, $params);
        if (count($results->getItems()) == 0) {
            $eventsRes[] = 'events list is empty';
        } else {
            foreach ($results->getItems() as $event) {
                $start = $event->start->dateTime;
                if (empty($start)) {
                    $start = $event->start->date;
                }
                $eventsRes[] = $event->getSummary().' '.$start;
            }
        }
        print_r($events);
    }
}
