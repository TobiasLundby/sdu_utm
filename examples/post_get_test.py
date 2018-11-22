#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
Descriptors: TL = Tobias Lundby (tolu@mmmi.sdu.dk)
2018-09-20 TL Created file
"""

"""
Description:
Example of how to get tehcnical data for a UAV using the 'Get UAV technical data' API
License: BSD 3-Clause
"""

import sys
import requests
import json
from termcolor import colored

# Disable warning
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)

if __name__ == '__main__':
    # POST THE DATA
    payload = {
        'uav_id': 3000,
        'uav_auth_key': 'abcd1234',
        'uav_op_status': 3,
        'pos_cur_lat_dd': 55.371653,
        'pos_cur_lng_dd': 10.428223,
        'pos_cur_alt_m': 30,
        'pos_cur_hdg_deg': 7,
        'pos_cur_vel_mps': 10,
        'pos_cur_gps_timestamp': 123456,
        'wp_next_lat_dd': 55.371653,
        'wp_next_lng_dd': 10.428223,
        'wp_next_alt_m': 10,
        'wp_next_hdg_deg': 0,
        'wp_next_vel_mps': 5,
        'wp_next_eta_epoch': 1537428516,
        'uav_bat_soc': 100
    }
    print colored('Trying to POST the data...', 'yellow')
    r = ''
    try:
        r = requests.post(url = 'https://droneid.dk/rmuasd/utm/tracking_data.php', data = payload, timeout=2)
        r.raise_for_status()
    except requests.exceptions.Timeout:
	    # Maybe set up for a retry, or continue in a retry loop
		print colored('Request has timed out', 'red')
    except requests.exceptions.TooManyRedirects:
        # Tell the user their URL was bad and try a different one
        print colored('Request has too many redirects', 'red')
    except requests.exceptions.HTTPError as err:
        print colored('HTTP error', 'red')
        print colored(err, 'yellow')
        #sys.exit(1) # Consider the exit since it might be unintentional in some cases
    except requests.exceptions.RequestException as err:
        # Catastrophic error; bail.
        print colored('Request error', 'red')
        print colored(err, 'yellow')
        sys.exit(1)
    else:
        if r.text == '1': # This check can in theory be omitted since the header check should catch an error
            print colored('Success!\n', 'green')
            print colored('Status code: %i' % r.status_code, 'yellow')
            print colored('Content type: %s' % r.headers['content-type'], 'yellow')

    # GET THE DATA AGAIN
    payload = {
        'time_delta_s': 1
    }
    r = ''
    try:
        r = requests.get(url = 'https://droneid.dk/rmuasd/utm/tracking_data.php', params = payload, timeout=2)
        r.raise_for_status()
    except requests.exceptions.Timeout:
	    # Maybe set up for a retry, or continue in a retry loop
		print colored('Request has timed out', 'red')
    except requests.exceptions.TooManyRedirects:
        # Tell the user their URL was bad and try a different one
        print colored('Request has too many redirects', 'red')
    except requests.exceptions.HTTPError as err:
        print colored('HTTP error', 'red')
        print colored(err, 'yellow')
        #sys.exit(1) # Consider the exit since it might be unintentional in some cases
    except requests.exceptions.RequestException as err:
        # Catastrophic error; bail.
        print colored('Request error', 'red')
        print colored(err, 'yellow')
        sys.exit(1)
    else:
        print colored('Status code: %i' % r.status_code, 'yellow')
        print colored('Content type: %s\n' % r.headers['content-type'], 'yellow')

        #print r.text

        if r.status_code == 204:
            print colored('No data matching the input parameters', 'yellow')
        else:
            data_dict = ''
            try:
                data_dict = json.loads(r.text) # convert to json
            except:
                print colored('Error in parsing of data to JSON', 'red')
            else:
                #print json.dumps(data_dict)
                #exit(1)
                #print r.text # Print the raw body data
                for entry in data_dict: # The loop could be omitted since there should only be 1 entry and the header exception should catch a request for a UAV ID which does not exist.
                    print( 'UAV ID: %i, uav_op_status: %i, uav_bat_soc: %i, time_epoch: %i' % (entry['uav_id'], entry['uav_op_status'], entry['uav_bat_soc'], entry['time_epoch']) )
                    print( 'Current pos: lat_dd: %f, lng_dd: %f, alt_m: %f, hdg_deg: %f, vel_mps: %f, gps timestamp: %i \n' % (entry['pos_cur_lat_dd'], entry['pos_cur_lng_dd'], entry['pos_cur_alt_m'], entry['pos_cur_hdg_deg'], entry['pos_cur_vel_mps'], entry['pos_cur_gps_timestamp']) )
