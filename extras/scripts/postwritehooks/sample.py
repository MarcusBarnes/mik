"""
Scripts that are not written in PHP can be used.

This is a sample script that writes some data to a file.
"""

import sys
import time
from ConfigParser import SafeConfigParser

# Grab the first parameter, which will be a string.
record_key = sys.argv[1]

# The second paramter will be a comma-separated list containing
# zero or more children record keys.
children_record_keys = sys.argv[2].split(',')

# The third parameter will be the path to the MIK .ini file.
parser = SafeConfigParser()
parser.read(sys.argv[3])

# Print the parameters to a log.
file = open("/tmp/sample.py.txt", "a")
file.write("Record key in sample.py: %s\n" % record_key)
# Convert the array into string so we can print it to the log.
children_string = ', '.join(children_record_keys)
file.write("Children record keys in sample.py: %s\n" % children_string)
# Access a value from the .ini file.
file.write("CONTENTdm web services URL from sample.py: %s\n" % parser.get('FETCHER', 'record_key'))
