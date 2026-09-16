#!/usr/bin/env python3
"""Create upload-ready WordPress theme and plugin archives."""

from pathlib import Path
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
