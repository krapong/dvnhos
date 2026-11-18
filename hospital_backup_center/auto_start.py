"""Supervise the local web server and restart it after an unexpected exit."""
import logging
import os
import subprocess
import sys
import time
import urllib.request
from logging.handlers import TimedRotatingFileHandler
from pathlib import Path
from dotenv import dotenv_values

ROOT=Path(__file__).resolve().parent

def healthy(port):
    try:
        with urllib.request.urlopen(f'http://127.0.0.1:{port}/login',timeout=5) as response:
            return response.status==200 and b'Hospital Backup' in response.read(65536)
    except Exception:return False

def main():
    # An exclusive local file lock prevents simultaneous startup/manual supervisors.
    import msvcrt
    data=ROOT/'data';data.mkdir(exist_ok=True)
    lock=(data/'autostart.lock').open('a+b')
    lock.seek(0);lock.write(b'0');lock.flush();lock.seek(0)
    try:msvcrt.locking(lock.fileno(),msvcrt.LK_NBLCK,1)
    except OSError:lock.close();return
    logs=data/'runtime';logs.mkdir(exist_ok=True)
    handler=TimedRotatingFileHandler(logs/'supervisor.log',when='midnight',backupCount=90,encoding='utf-8')
    handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s %(message)s'))
    logging.getLogger().addHandler(handler);logging.getLogger().setLevel(logging.INFO)
    port=int(dotenv_values(ROOT/'.env').get('PORT','8088'))
    logging.info('Autostart supervisor started')
    while True:
        if healthy(port):
            time.sleep(30)
            continue
        try:
            logging.info('Starting web server')
            with (logs/'startup-errors.log').open('ab') as errors:
                child=subprocess.Popen([sys.executable,str(ROOT/'serve.py')],cwd=str(ROOT),stdin=subprocess.DEVNULL,stdout=subprocess.DEVNULL,stderr=errors,creationflags=subprocess.CREATE_NO_WINDOW)
                (data/'web-server.pid').write_text(str(child.pid),encoding='ascii')
                code=child.wait()
            logging.warning('Web server exited with code %s; retrying in 10 seconds',code)
        except Exception as error:
            logging.error('Startup failed: %s',type(error).__name__)
        time.sleep(10)

if __name__=='__main__':main()
