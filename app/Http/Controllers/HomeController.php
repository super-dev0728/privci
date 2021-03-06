<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Search;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $type = 'all';

        $domains = Search::get_all_domains();
        if($request->session()->get('selected_domain') == NULL) {
            foreach($domains as $domain) {
                $first_domain_detail = Search::get_domain_detail($domain->monitoring_domain);
                $first_domain_emails = Search::get_domain_emails($domain->monitoring_domain);
                $domain_detail = $first_domain_detail[0];
                $domain_emails = explode(',', $first_domain_emails[0]->colleague_emails);
                $selected = $domain->monitoring_domain;

                break;
            }
        } else {
            $selected = $request->session()->get('selected_domain');

            $result = Search::get_domain_detail($selected);
            $domain_emails = Search::get_domain_emails($selected);
            $domain_detail = $result[0];
            $domain_emails = explode(',', $domain_emails[0]->colleague_emails);
        }

        $width = 180;

        return view('home', compact('domains', 'domain_detail', 'domain_emails', 'type', 'selected', 'width'));
    }

    public function get_by_domain(Request $request) {
        $type = "one";
        $domain = $request->domain;

        $domains = Search::get_all_domains();
        $domain_detail = Search::get_domain_detail($domain);
        $domain_detail = $domain_detail[0];
        $domain_emails = Search::get_domain_emails($domain);
        if(!empty($domain_emails)) {
            $domain_emails = explode(',', $domain_emails[0]->colleague_emails);
        } else {
            $domain_emails = array();
        }
        
        $data['selected'] = $domain;
        $request->session()->put('selected_domain', $domain);
        $width = (int)$request->width;

        $data['html'] = view('__home', compact('domains', 'domain_detail', 'domain_emails', 'type', 'width'))->render();

        return response()->json($data);
    }

    public function sort_email(Request $request) {
        $domains = Search::get_domains_by_order($request);

        $data['html'] = '';
        $data['order'] = $request->order;
        $domain_list = array();
        $email_list = array();
        $array = array();
        $array1 = array();

        foreach($domains as $domain) {
            $email = Search::get_domain_emails($domain->monitoring_domain);
            $domain_emails = explode(',', $email[0]->colleague_emails);
            $array['monitoring_domain'] = $domain->monitoring_domain;
            $array1['email_count'] = count($domain_emails);
            array_push($domain_list, $array);
            array_push($email_list, $array1);
        }

        if($request->order == 'asc') {
            array_multisort($email_list, SORT_ASC, $domain_list);
        } else {
            array_multisort($email_list, SORT_DESC, $domain_list);
        }

        for($i=0; $i<count($domain_list); $i++) {
            if($domain_list[$i]['monitoring_domain'] == $request->selected) {
                $data['html'] .= '<div class="form-check d-flex align-items-center title="' . $domain_list[$i]['monitoring_domain'] . '"">
                                <input class="form-check-input" type="radio" name="domain_name" value="' . $domain_list[$i]['monitoring_domain'] . '" checked>
                                <label class="form-check-label text-white ellipsis">' . $domain_list[$i]['monitoring_domain'] . '</label>
                            </div>';
            } else {
                $data['html'] .= '<div class="form-check d-flex align-items-center title="' . $domain_list[$i]['monitoring_domain'] . '"">
                                <input class="form-check-input" type="radio" name="domain_name" value="' . $domain_list[$i]['monitoring_domain'] . '">
                                <label class="form-check-label text-white ellipsis">' . $domain_list[$i]['monitoring_domain'] . '</label>
                            </div>';
            }
        }

        return response()->json($data);
    }

    public function sort_domain(Request $request) {
        $domains = Search::get_domains_by_order($request);

        $data['html'] = '';
        $data['order'] = $request->order;

        foreach($domains as $domain) {
            if($domain->monitoring_domain == $request->selected) {
                $data['html'] .= '<div class="form-check d-flex align-items-center">
                                <input class="form-check-input" type="radio" name="domain_name" value="' . $domain->monitoring_domain . '" checked>
                                <label class="form-check-label text-white ellipsis">' . $domain->monitoring_domain . '</label>
                            </div>';
            } else {
                $data['html'] .= '<div class="form-check d-flex align-items-center">
                                <input class="form-check-input" type="radio" name="domain_name" value="' . $domain->monitoring_domain . '">
                                <label class="form-check-label text-white ellipsis">' . $domain->monitoring_domain . '</label>
                            </div>';
            }
            
        }

        return response()->json($data);
    }

    public function search_by_keyword(Request $request) {
        $domains = Search::get_domains_by_keyword($request);
        $data['html'] = '';

        if(!empty($domains)) {
            foreach($domains as $domain) {
                if($domain->monitoring_domain == $request->selected) {
                    $data['html'] .= '<div class="form-check d-flex align-items-center">
                                    <input class="form-check-input" type="radio" name="domain_name" value="' . $domain->monitoring_domain . '" checked>
                                    <label class="form-check-label text-white ellipsis">' . $domain->monitoring_domain . '</label>
                                </div>';
                } else {
                    $data['html'] .= '<div class="form-check d-flex align-items-center">
                                    <input class="form-check-input" type="radio" name="domain_name" value="' . $domain->monitoring_domain . '">
                                    <label class="form-check-label text-white ellipsis">' . $domain->monitoring_domain . '</label>
                                </div>';
                }
                
            }
        } else {
            $data['html'] = '<p class="no-domain">No result found!</p>';
        }

        return response()->json($data);
    }

    public function search_by_email(Request $request) {
        $emails = Search::get_domain_emails($request->selected);
        $emails = explode(',', $emails[0]->colleague_emails);
        $data['html'] = '';
        $data['no_email'] = false;
        $data['domain'] = $request->selected;

        foreach($emails as $email) {
            if(trim($email) == '') continue;
            
            if(str_contains(trim($email), $request->keyword)) {
                $data['html'] .= '<div class="col-lg-4 col-md-6">
                                    <div class="form-check">
                                        <label class="form-check-label ellipsis" for="">
                                            <input type="checkbox" class="form-check-input" name="sel_email[]" value="' . $email . '">' . $email . '
                                        </label>
                                    </div>
                                </div>';
            }
        }

        if($data['html'] == '') {
            $data['html'] = '<div class="no-emails">No Emails!</div>';
            $data['no_email'] = true;
        }

        return response()->json($data);
    }

    public function get_breach_info(Request $request) {
        $domain_detail = Search::get_domain_detail($request->domain);
        $breach = json_decode($domain_detail[0]->breach_info);
        $array = get_object_vars($breach);
        $data['breach_info'] = get_object_vars($array[$request->page]);
        $data['index'] = $request->page;

        return response()->json($data);
    }

    public function get_emails_by_pagination(Request $request) {
        $emails = Search::get_domain_emails($request->domain);
        $emails = explode(',', $emails[0]->colleague_emails);
        $total_count = count($emails) - 1;

        $data['html'] = '';
        $emails = array_slice($emails, 30 * $request->page + 1, 30);

        $data['start'] = 30 * $request->page + 1;
        $data['end'] = 30 * $request->page + count($emails);
        
        foreach($emails as $email) {
            if(trim($email) == '') continue;
            
            $data['html'] .= '<div class="col-lg-4 col-md-6">
                                <div class="form-check">
                                    <label class="form-check-label ellipsis" for="">
                                        <input type="checkbox" class="form-check-input" name="sel_email[]" value="' . $email . '">' . $email . '
                                    </label>
                                </div>
                            </div>';
        }

        if($total_count > ($request->page + 1) * 30) {
            $data['next'] = $request->page + 1;
        }
        
        $data['page'] = $request->page * 1;

        return response()->json($data);
    }
}