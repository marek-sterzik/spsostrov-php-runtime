#!/usr/bin/env python3

import time
import RPi.GPIO as GPIO

def init():
    GPIO.setmode(GPIO.BCM)
    for pin in pins:
        GPIO.setup(pin, GPIO.OUT)
        GPIO.output(pin, GPIO.HIGH)

def gpio_run(timeout, clear, only_up, pins):
    last_pin = None
    for pin in pins:
        GPIO.output(pin, GPIO.LOW)
        if last_pin != None and clear:
            GPIO.output(last_pin, GPIO.HIGH)
        last_pin = pin
        if timeout != 0:
            time.sleep(timeout)
    if not only_up:
        for pin in reversed(pins):
            if last_pin == pin:
                continue
            GPIO.output(pin, GPIO.LOW)
            GPIO.output(last_pin, GPIO.HIGH)
            last_pin = pin
            if timeout != 0:
                time.sleep(timeout)
        if last_pin != None:
            GPIO.output(last_pin, GPIO.HIGH)
            if timeout != 0:
                time.sleep(timeout)


pins=[5, 6, 13, 16, 19, 20, 21, 26]

init()

gpio_run(0.05, True, False, pins)
gpio_run(0.05, True, False, pins)
gpio_run(0.05, True, False, pins)
time.sleep(1)

gpio_run(0.05, False, False, pins)
gpio_run(0.05, False, False, pins)
gpio_run(0.05, False, False, pins)
time.sleep(1)

gpio_run(0, False, True, pins)
time.sleep(1)

GPIO.cleanup()
