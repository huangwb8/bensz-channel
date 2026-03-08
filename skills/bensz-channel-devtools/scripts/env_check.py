from __future__ import annotations

import argparse
import sys
from pathlib import Path

from _bdc_env import resolve_bdc_env


def main() -> int:
    parser = argparse.ArgumentParser(description="检查 BENSZ_CHANNEL_URL + BENSZ_CHANNEL_KEY 配置（不泄露密钥）。")
    parser.add_argument("--env-file", type=str, default=None, help="可选 .env 文件路径。")
    args = parser.parse_args()

    skill_root = Path(__file__).resolve().parents[1]
    env_file = Path(args.env_file).expanduser() if args.env_file else None
    env = resolve_bdc_env(skill_root=skill_root, env_file=env_file)

    problems: list[str] = []
    if not env.url:
        problems.append("缺少 URL：请设置 BENSZ_CHANNEL_URL（或 bdc_url）")
    if not env.key:
        problems.append("缺少 KEY：请设置 BENSZ_CHANNEL_KEY（或 bdc_key）")
    if env.key and len(env.key) < 20:
        problems.append("KEY 长度不足（需要 >= 20）")

    print("bensz-channel-devtools env check")
    print(f"- url: {env.url} (source={env.url_source.kind}:{env.url_source.detail})")
    print(f"- key: {env.key_prefix()} (source={env.key_source.kind}:{env.key_source.detail})")

    if problems:
        print("\n问题：")
        for p in problems:
            print(f"- {p}")
        return 2

    print("\nOK — 请继续运行 `python3 scripts/client.py ping` 验证连接。")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
