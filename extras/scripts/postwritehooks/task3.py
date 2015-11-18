"""
Scripts that are not written in PHP can be used, but they probably should
ignore the third parameter, which is the JSONized MIK .ini file.
"""

import sys
import time
import json

# Grab the first parameter, which will be a string.
record_key = sys.argv[1]

# The second paramter will be a JSON list that contains 0 or more
# children record keys.
children_record_keys = json.loads(sys.argv[2])

# Tasks can be long running...
time.sleep(3)

# Print the parameters to a log.
file = open("/tmp/task3.txt", "a")
file.write("Record key in task3.py: %s\n" % record_key)
# Convert the array into string so we can print it to the log.
children_string = ', '.join(children_record_keys)
file.write("Children record keys in task3.py: %s\n" % children_string)
