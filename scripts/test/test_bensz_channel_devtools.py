#!/usr/bin/env python3
from __future__ import annotations

import importlib.util
import sys
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

ROOT_DIR = Path(__file__).resolve().parents[2]
SKILL_ROOT = ROOT_DIR / 'skills' / 'bensz-channel-devtools'
SCRIPT_DIR = SKILL_ROOT / 'scripts'

if str(SCRIPT_DIR) not in sys.path:
    sys.path.insert(0, str(SCRIPT_DIR))

spec = importlib.util.spec_from_file_location('bdc_client_test', SCRIPT_DIR / 'client.py')
assert spec and spec.loader
client = importlib.util.module_from_spec(spec)
spec.loader.exec_module(client)

from _bdc_env import resolve_bdc_env  # type: ignore  # noqa: E402
from _http_json import HttpResult  # type: ignore  # noqa: E402


class DevtoolsSkillCliTest(unittest.TestCase):
    def setUp(self) -> None:
        self.temp_dir = tempfile.TemporaryDirectory()
        self.env_path = Path(self.temp_dir.name) / '.env'
        self.env_path.write_text(
            'BENSZ_CHANNEL_URL=http://127.0.0.1:6542\n'
            'BENSZ_CHANNEL_KEY=bdc_xxxxxxxxxxxxxxxxxxxxxxxx\n',
            encoding='utf-8',
        )
        self.env = resolve_bdc_env(skill_root=SKILL_ROOT, env_file=self.env_path)
        self.original_dry_run = client.DRY_RUN

    def tearDown(self) -> None:
        client.DRY_RUN = self.original_dry_run
        self.temp_dir.cleanup()

    def invoke_cli(self, *argv: str):
        records: list[dict[str, object]] = []
        with patch.object(client, '_print_json', side_effect=records.append):
            rc = client.main(['--env', str(self.env_path), '--dry-run', *argv])
        return rc, records

    def find_request(self, records: list[dict[str, object]], method: str, path_fragment: str) -> dict[str, object]:
        for record in records:
            if record.get('method') == method and path_fragment in str(record.get('url', '')):
                return record
        self.fail(f'未找到请求: method={method}, path_fragment={path_fragment}, records={records!r}')

    def test_channels_create_supports_show_in_top_nav_flag(self) -> None:
        rc, records = self.invoke_cli(
            'channels', 'create',
            '--name', '公告',
            '--icon', '📢',
            '--accent-color', '#3b82f6',
            '--show-in-top-nav', 'false',
        )
        self.assertEqual(rc, 0)
        request = self.find_request(records, 'POST', '/api/vibe/channels')
        self.assertEqual(request['json_body']['show_in_top_nav'], False)

    def test_articles_list_supports_pinned_and_featured_filters(self) -> None:
        rc, records = self.invoke_cli(
            'articles', 'list',
            '--channel-id', '3',
            '--published', 'false',
            '--pinned', 'true',
            '--featured', 'false',
        )
        self.assertEqual(rc, 0)
        request = self.find_request(records, 'GET', '/api/vibe/articles')
        url = str(request['url'])
        self.assertIn('channel_id=3', url)
        self.assertIn('published=false', url)
        self.assertIn('pinned=true', url)
        self.assertIn('featured=false', url)

    def test_articles_update_supports_operational_fields(self) -> None:
        rc, records = self.invoke_cli(
            'articles', 'update',
            '--id', 'article-42',
            '--channel-id', '9',
            '--title', '新标题',
            '--slug', 'new-title',
            '--published', 'false',
            '--published-at', '2026-03-10T08:00:00+08:00',
            '--pinned', 'true',
            '--featured', 'false',
            '--cover-gradient', 'from-sky-500 via-cyan-500 to-blue-500',
        )
        self.assertEqual(rc, 0)
        request = self.find_request(records, 'PUT', '/api/vibe/articles/article-42')
        body = request['json_body']
        self.assertEqual(body['channel_id'], 9)
        self.assertEqual(body['slug'], 'new-title')
        self.assertEqual(body['is_published'], False)
        self.assertEqual(body['is_pinned'], True)
        self.assertEqual(body['is_featured'], False)
        self.assertEqual(body['published_at'], '2026-03-10T08:00:00+08:00')
        self.assertEqual(body['cover_gradient'], 'from-sky-500 via-cyan-500 to-blue-500')

    def test_users_update_supports_avatar_url(self) -> None:
        rc, records = self.invoke_cli(
            'users', 'update',
            '--id', '7',
            '--role', 'admin',
            '--avatar-url', 'https://cdn.example.com/avatar.png',
        )
        self.assertEqual(rc, 0)
        request = self.find_request(records, 'PUT', '/api/vibe/users/7')
        self.assertEqual(request['json_body']['role'], 'admin')
        self.assertEqual(request['json_body']['avatar_url'], 'https://cdn.example.com/avatar.png')

    def test_users_delete_command_is_available(self) -> None:
        rc, records = self.invoke_cli('users', 'delete', '--id', '11')
        self.assertEqual(rc, 0)
        request = self.find_request(records, 'DELETE', '/api/vibe/users/11')
        self.assertEqual(request['method'], 'DELETE')

    def test_doctor_returns_failure_when_heartbeat_is_not_ok(self) -> None:
        client.DRY_RUN = False

        def fake_call(method: str, url: str, **_: object) -> HttpResult:
            if url.endswith('/api/vibe/ping'):
                return HttpResult(status=200, headers={}, body_text='{"ok":true}', json={'ok': True})
            if url.endswith('/api/vibe/connect'):
                return HttpResult(status=200, headers={}, body_text='{"connectionId":"conn-1"}', json={'connectionId': 'conn-1'})
            if url.endswith('/api/vibe/heartbeat'):
                return HttpResult(status=500, headers={}, body_text='{"error":"boom"}', json={'error': 'boom'})
            if url.endswith('/api/vibe/disconnect'):
                return HttpResult(status=200, headers={}, body_text='{"ok":true}', json={'ok': True})
            raise AssertionError(f'未预期请求: {method} {url}')

        with patch.object(client, '_call', side_effect=fake_call), patch.object(client, '_print_json', side_effect=lambda *_args, **_kwargs: None):
            rc = client.cmd_doctor(self.env, 5)

        self.assertEqual(rc, 1)


if __name__ == '__main__':
    unittest.main(verbosity=2)
