import importlib.util
import os
import tempfile
import unittest
from pathlib import Path

spec=importlib.util.spec_from_file_location('retention',Path(__file__).resolve().parents[1]/'remote'/'prune-backups.py')
retention=importlib.util.module_from_spec(spec)
spec.loader.exec_module(retention)

class RetentionTests(unittest.TestCase):
    def test_keeps_three_and_preserves_unmarked(self):
        with tempfile.TemporaryDirectory() as d:
            root=Path(d)
            for n in range(1,6):
                f=root/f'202611{n:02}_160000';f.mkdir()
                marker=f/'.hbc-complete';marker.write_text('HBC_DB_ONLY_V1')
                os.utime(marker,(100000+n,100000+n))
            unmarked=root/'20261001_160000';unmarked.mkdir()
            retention.prune(root)
            self.assertEqual(sorted(p.name for p in root.iterdir()),['20261001_160000','20261103_160000','20261104_160000','20261105_160000'])
            retention.prune(root)
            self.assertEqual(len(list(root.iterdir())),4)

    def test_under_limit_and_timestamp_ties(self):
        with tempfile.TemporaryDirectory() as d:
            root=Path(d)
            for n in range(1,5):
                f=root/f'202611{n:02}_160000';f.mkdir()
                marker=f/'.hbc-complete';marker.write_text('HBC_DB_ONLY_V1');os.utime(marker,(100000,100000))
                if n<4:
                    retention.prune(root)
                    self.assertEqual(len(list(root.iterdir())),n)
            retention.prune(root)
            self.assertFalse((root/'20261101_160000').exists())
            self.assertEqual(len(list(root.iterdir())),3)

if __name__=='__main__':unittest.main()
