#!/usr/bin/env python

# Chris Eustis
# ECE 331 Project 2
# Due: May 2, 2016
#
# This script reads the temperature sensor over i2c and stores
# the temperature and current time into a sqlite3 database.
# The purpose of this script is to run only once and is
# executed using cron once every minute.

import smbus
import time
import sqlite3 as lite
import datetime
import sys

# Temperature sensor initialization
bus = smbus.SMBus(1)
address = 0x48

# This script triggered by Cron every 1 minute
# Read byte of data (temperature in C)
temp = bus.read_byte_data(address, 0x00)
# Read the current time
t = datetime.datetime.now()

# Print out for verification
print(t)
print(temp)

# Connect to database
db = lite.connect('/var/www/html/templogger/readTemp/templogger.db')
with db:
    c = db.cursor()
    print "Opened database successfully"

    # Insert new temp into database
    try:
        print "Writing to database..."
        c.execute('''INSERT INTO tempLog(datetime, temperature) VALUES(?,?)''', (t, temp))
        print "Wrote to database successfully"
    except:
        db.rollback()
        print "Failed writing to database :("

c.close()
db.close()
