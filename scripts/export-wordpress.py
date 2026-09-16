#!/usr/bin/env python3
"""Create upload-ready WordPress theme and plugin archives."""

from pathlib import Path
import re
import sys
import zipfile


PROJECT_ROOT = Path(__file__).resolve().parents[1]
DEFAULT_OUTPUT = PROJECT_ROOT / "wp-content"

PACKAGES = (
    ("lgsdn", PROJECT_ROOT / "wp-content/themes/lgsdn", "themes/lgsdn-theme.zip", set()),
    (
        "lgsdn-core",
        PROJECT_ROOT / "wp-content/plugins/lgsdn-core",
        "plugins/lgsdn-core.zip",
        {"tests"},
    ),
)


def bump_versions() -> str:
    theme_file = PROJECT_ROOT / "wp-content/themes/lgsdn/style.css"
    plugin_file = PROJECT_ROOT / "wp-content/plugins/lgsdn-core/lgsdn-core.php"
    theme = theme_file.read_bytes().decode("utf-8")
    plugin = plugin_file.read_bytes().decode("utf-8")

    theme_match = re.search(r"^Version:\s*(\d+)\.(\d+)\.(\d+)\s*$", theme, re.MULTILINE)
    plugin_match = re.search(r"^ \* Version:\s*(\d+)\.(\d+)\.(\d+)\s*$", plugin, re.MULTILINE)
    if not theme_match or not plugin_match:
        raise ValueError("Could not find theme and plugin versions")

    theme_version = tuple(map(int, theme_match.groups()))
    plugin_version = tuple(map(int, plugin_match.groups()))
    if theme_version != plugin_version:
        raise ValueError("Theme and plugin versions do not match")

    version = f"{theme_version[0]}.{theme_version[1]}.{theme_version[2] + 1}"
    theme = re.sub(r"^Version:\s*\d+\.\d+\.\d+\s*$", f"Version: {version}", theme, count=1, flags=re.MULTILINE)
    plugin = re.sub(r"^( \* Version:)\s*\d+\.\d+\.\d+\s*$", rf"\g<1> {version}", plugin, count=1, flags=re.MULTILINE)
    plugin = re.sub(r"(define\(\s*'LGSDN_CORE_VERSION',\s*')[^']+(')", rf"\g<1>{version}\g<2>", plugin, count=1)

    theme_file.write_bytes(theme.encode("utf-8"))
    plugin_file.write_bytes(plugin.encode("utf-8"))
    return version


def add_directory(archive: zipfile.ZipFile, source: Path, archive_root: str, excluded: set[str]) -> None:
    for path in sorted(source.rglob("*")):
        relative = path.relative_to(source)
        if any(part in excluded for part in relative.parts):
            continue

        archive_name = Path(archive_root, relative).as_posix()
        if path.is_dir():
            archive.writestr(f"{archive_name}/", "")
        else:
            archive.write(path, archive_name)


def main() -> int:
    output_root = Path(sys.argv[1]).expanduser() if len(sys.argv) > 1 else DEFAULT_OUTPUT
    output_root.mkdir(parents=True, exist_ok=True)
    version = bump_versions()
    print(f"Bumped theme and plugin version to {version}")

    for archive_root, source, relative_archive, excluded in PACKAGES:
        if not source.is_dir():
            raise FileNotFoundError(f"Source directory not found: {source}")

        archive_path = output_root / relative_archive
        archive_path.parent.mkdir(parents=True, exist_ok=True)
        with zipfile.ZipFile(archive_path, "w", compression=zipfile.ZIP_DEFLATED) as archive:
            add_directory(archive, source, archive_root, excluded)

        size_kb = archive_path.stat().st_size / 1024
        print(f"Created {archive_path} ({size_kb:.1f} KB)")

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
