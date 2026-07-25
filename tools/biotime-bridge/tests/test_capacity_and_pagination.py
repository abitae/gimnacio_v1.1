import unittest
import sys
import types

try:
    import requests  # noqa: F401
except ModuleNotFoundError:
    sys.modules["requests"] = types.ModuleType("requests")

from bridge.biotime_client import BioTimeClient
from bridge.runner import BridgeRunner


class FakePagedBioTime(BioTimeClient):
    def __init__(self, total):
        self.total = total

    def list_employees(self, page=1, page_size=200, params=None):
        start = (page - 1) * page_size
        end = min(self.total, start + page_size)
        rows = [{"id": index + 1} for index in range(start, end)]
        next_page = page + 1 if end < self.total else None
        return rows, {"next": next_page, "count": self.total}


class BridgeCapacityTest(unittest.TestCase):
    def runner(self, reported_count=499, source="terminal_counter"):
        runner = object.__new__(BridgeRunner)
        runner._reserved_additions = 0
        runner.remote_config = {
            "capacity_enforcement_enabled": True,
            "hard_limit": 500,
            "capacity": {
                "inventory_ready": True,
                "selected_count": 500,
                "client_slots": 500,
            },
            "devices": [
                {
                    "access_enabled": True,
                    "inventory_verified": True,
                    "inventory_source": source,
                    "reported_users_count": reported_count,
                    "capacity_limit": 500,
                }
            ],
        }
        return runner

    def test_blocks_creation_at_500_and_counts_local_reservations(self):
        runner = self.runner(reported_count=499)
        self.assertTrue(runner._can_create_employee())

        runner._reserved_additions = 1
        self.assertFalse(runner._can_create_employee())
        self.assertFalse(self.runner(reported_count=500)._can_create_employee())

    def test_projection_is_not_accepted_as_verified_terminal_inventory(self):
        runner = self.runner(source="biotime_area_projection")
        self.assertFalse(runner._can_create_employee())

    def test_employee_pagination_does_not_stop_at_first_200(self):
        rows = FakePagedBioTime(total=550).list_all_employees(page_size=200)
        self.assertEqual(550, len(rows))
        self.assertEqual(550, rows[-1]["id"])


if __name__ == "__main__":
    unittest.main()
