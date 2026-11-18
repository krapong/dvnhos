"""Windows WSGI entry point; binds to localhost only."""
import logging
import os
from logging.handlers import TimedRotatingFileHandler
from pathlib import Path
from waitress import serve
from app import app

ROOT = Path(__file__).resolve().parent

def main():
    logs=ROOT/'data'/'runtime'
    logs.mkdir(parents=True,exist_ok=True)
    handler=TimedRotatingFileHandler(logs/'server.log',when='midnight',backupCount=90,encoding='utf-8')
    handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s %(name)s %(message)s'))
    logging.getLogger().addHandler(handler)
    logging.getLogger().setLevel(logging.INFO)
    logging.info('Hospital Backup Center starting on localhost')
    serve(app,host='127.0.0.1',port=int(os.getenv('PORT','8088')),threads=8,channel_timeout=300)

if __name__=='__main__':main()
