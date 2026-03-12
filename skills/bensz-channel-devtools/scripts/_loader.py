from __future__ import annotations

from pathlib import Path


def _candidate_roots() -> list[Path]:
    homes = [Path.home() / ".codex" / "skills", Path.home() / ".claude" / "skills"]

    return [
        root / "bensz-channel-vibe-config" / "scripts"
        for root in homes
    ]


def _real_module_path(name: str) -> Path:
    for scripts_dir in _candidate_roots():
        target = scripts_dir / f"{name}.py"
        if target.is_file():
            return target

    raise FileNotFoundError(f"未找到 bensz-channel-vibe-config 脚本：{name}.py")


def exec_real_module(name: str, namespace: dict[str, object]) -> None:
    target = _real_module_path(name)
    namespace["__file__"] = str(target)
    namespace["__package__"] = None

    code = compile(target.read_text(encoding="utf-8"), str(target), "exec")
    exec(code, namespace)
