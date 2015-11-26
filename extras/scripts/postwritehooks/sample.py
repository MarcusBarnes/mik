"""
Scripts that are not written in PHP can be used. They can access MIK
configuration values in the JSONized parameters.
"""

import sys
import time
import json

# Grab the first parameter, which will be a string.
record_key = sys.argv[1]

# The second paramter will be a JSON list that contains 0 or more
# children record keys.
children_record_keys = json.loads(sys.argv[2])

# The third parameter will be a JSON object that doesn't decode in
# Python reliably. It's best to not try to decode it but to define
# configuration values within the Python script itself.
output_directory = '/some/path'

# Print the parameters to a log.
file = open("/tmp/sample.py.txt", "a")
file.write("Record key in sample.py: %s\n" % record_key)
# Convert the array into string so we can print it to the log.
children_string = ', '.join(children_record_keys)
file.write("Children record keys in sample.py: %s\n" % children_string)
