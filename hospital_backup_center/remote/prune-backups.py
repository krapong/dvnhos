#!/usr/bin/env python3
"""Keep only the three newest completed Hospital Backup Center sets."""
import re
import shutil
import sys
from pathlib import Path

KEEP_COPIES = 3

def prune(root):
    root = Path(root)
    if root.is_symlink() or not root.is_dir():
        raise ValueError('Backup root must be an existing real directory')
    root = root.resolve()
    completed = []
    for folder in root.iterdir():
        if folder.is_symlink() or not folder.is_dir() or not re.fullmatch(r'\d{8}_\d{6}', folder.name):
            continue
        marker = folder / '.hbc-complete'
        if marker.is_symlink() or not marker.is_file():
            continue
        if marker.read_text().strip() != 'HBC_DB_ONLY_V1':
            continue
        completed.append((marker.stat().st_mtime, folder))
    completed.sort(key=lambda item: (item[0], item[1].name), reverse=True)
    for stamp, folder in completed[KEEP_COPIES:]:
        if folder.resolve().parent == root:
            shutil.rmtree(folder)
            print('PRUNED ' + str(folder))

if __name__ == '__main__':
    prune(sys.argv[1])
