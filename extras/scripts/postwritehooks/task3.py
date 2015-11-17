"""
Scripts that are not written in PHP can be used, but they probably want to ignore
the second parameter, which is the JSONized MIK .ini file.
"""

import sys
import time

# Only grab the first parameter.
record_key = sys.argv[1]

# Tasks can be long running...
time.sleep(3)

file = open("/tmp/task3.txt", "a")
file.write("Record key in task3.py: %s\n" % record_key)
