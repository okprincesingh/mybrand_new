import json
import re
from pathlib import Path

import fitz


ROOT = Path(__file__).resolve().parents[1]
SOURCE_DIR = ROOT / "Private Label Products Line"
OUTPUT_DIR = ROOT / "uploads" / "products" / "private-label"
MANIFEST_PATH = ROOT / "scripts" / "private_label_manifest.json"


def slugify(text: str) -> str:
    text = text.strip().lower()
    text = re.sub(r"[^\w\s-]", "", text, flags=re.UNICODE)
    text = re.sub(r"[\s_]+", "-", text)
    text = re.sub(r"-{2,}", "-", text)
    return text.strip("-") or "item"


def clean_name(name: str) -> str:
    name = re.sub(r"\s+", " ", name.replace("_", " ").strip())
    return name


def normalize_fallback_name(name: str) -> str:
    name = clean_name(name)
    # Many catalog files are prefixed with placeholder brand text.
    name = re.sub(r"^(your|your logo)\s+", "", name, flags=re.IGNORECASE)
    return name.strip() or clean_name(name)


def is_placeholder_title(text: str) -> bool:
    t = clean_name(text).lower()
    if not t:
        return True
    if t in {"your", "logo", "your logo", "yourlogo"}:
        return True
    if t.startswith("your logo"):
        return True
    if t.startswith("your "):
        return True
    return False


def extract_title(doc: fitz.Document, fallback_name: str) -> str:
    fallback_clean = normalize_fallback_name(fallback_name)
    try:
        page = doc[0]
    except Exception:
        return fallback_clean

    best_text = ""
    best_score = -1.0
    text_dict = page.get_text("dict")
    for block in text_dict.get("blocks", []):
        for line in block.get("lines", []):
            for span in line.get("spans", []):
                text = clean_name(span.get("text", ""))
                if not text:
                    continue
                if len(text) < 4:
                    continue
                if not re.search(r"[A-Za-z]", text):
                    continue
                if is_placeholder_title(text):
                    continue
                lowered = text.lower()
                if "private label" in lowered:
                    continue
                if "ingredients" in lowered and len(text) < 20:
                    continue
                size = float(span.get("size", 0.0))
                score = size * (1.0 + min(len(text), 80) / 160.0)
                if score > best_score:
                    best_score = score
                    best_text = text

    if best_text and not is_placeholder_title(best_text):
        return best_text
    return fallback_clean


def extract_images(doc: fitz.Document, product_slug: str) -> list[str]:
    out_dir = OUTPUT_DIR / product_slug
    out_dir.mkdir(parents=True, exist_ok=True)

    saved: list[str] = []
    # Requirement: 1 PDF page = 1 exported image.
    matrix = fitz.Matrix(2, 2)  # ~144 DPI output for better clarity
    index = 1
    for page in doc:
        pix = page.get_pixmap(matrix=matrix, alpha=False)
        filename = f"{index:02d}.jpg"
        target = out_dir / filename
        pix.save(target)
        rel = target.relative_to(ROOT).as_posix()
        saved.append(rel)
        index += 1

    return saved


def build_manifest() -> dict:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    items = []
    skipped = []

    for pdf_path in sorted(SOURCE_DIR.rglob("*.pdf")):
        rel = pdf_path.relative_to(SOURCE_DIR)
        parts = list(rel.parts)
        if len(parts) < 2:
            skipped.append({"pdf": str(rel), "reason": "invalid-folder-structure"})
            continue

        top_category = clean_name(parts[0])
        sub_category = clean_name(parts[1]) if len(parts) >= 3 else ""
        file_base = clean_name(pdf_path.stem)

        try:
            doc = fitz.open(pdf_path)
        except Exception as exc:
            skipped.append({"pdf": str(rel), "reason": f"open-failed: {exc}"})
            continue

        title = extract_title(doc, file_base)
        slug = slugify(normalize_fallback_name(file_base))
        images = extract_images(doc, slug)
        doc.close()

        if not images:
            skipped.append({"pdf": str(rel), "reason": "no-images-found"})
            continue

        items.append(
            {
                "pdf": str(rel).replace("\\", "/"),
                "name": title,
                "name_fallback": normalize_fallback_name(file_base),
                "product_slug": slug,
                "top_category": top_category,
                "sub_category": sub_category,
                "images": images,
            }
        )

    return {"source_dir": str(SOURCE_DIR), "output_dir": str(OUTPUT_DIR), "items": items, "skipped": skipped}


if __name__ == "__main__":
    manifest = build_manifest()
    MANIFEST_PATH.write_text(json.dumps(manifest, indent=2, ensure_ascii=False), encoding="utf-8")
    print(f"Manifest written: {MANIFEST_PATH}")
    print(f"Products prepared: {len(manifest['items'])}")
    print(f"Skipped: {len(manifest['skipped'])}")
