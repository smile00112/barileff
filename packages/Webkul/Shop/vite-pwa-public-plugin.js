import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Writes Web App Manifest, copies service worker and PWA icons to Laravel public/ after Shop build.
 */
export function shopPwaPublicPlugin() {
    return {
        name: "shop-pwa-public-assets",
        apply: "build",
        closeBundle() {
            const publicDir = path.resolve(__dirname, "../../../public");
            const manifestPath = path.join(__dirname, "pwa.manifest.json");
            const swSrc = path.join(__dirname, "src/Resources/assets/pwa/sw.js");
            const iconsSrc = path.join(__dirname, "src/Resources/assets/pwa/icons");
            const iconsDest = path.join(publicDir, "pwa-icons");

            const name =
                process.env.VITE_PWA_NAME ||
                process.env.VITE_APP_NAME ||
                "Bagisto";
            const shortName =
                process.env.VITE_PWA_SHORT_NAME ||
                process.env.VITE_APP_NAME ||
                "Bagisto";
            const description =
                process.env.VITE_PWA_DESCRIPTION ||
                "Storefront";

            const escapeJsonInner = (value) =>
                JSON.stringify(value).slice(1, -1);

            let manifestBody = fs.readFileSync(manifestPath, "utf8");
            manifestBody = manifestBody
                .replace(/__VITE_PWA_NAME__/g, escapeJsonInner(name))
                .replace(/__VITE_PWA_SHORT_NAME__/g, escapeJsonInner(shortName))
                .replace(/__VITE_PWA_DESCRIPTION__/g, escapeJsonInner(description));

            fs.writeFileSync(
                path.join(publicDir, "manifest.webmanifest"),
                manifestBody,
                "utf8"
            );

            fs.copyFileSync(swSrc, path.join(publicDir, "sw.js"));

            fs.mkdirSync(iconsDest, { recursive: true });
            if (fs.existsSync(iconsSrc)) {
                for (const file of fs.readdirSync(iconsSrc)) {
                    if (file.toLowerCase().endsWith(".png")) {
                        fs.copyFileSync(
                            path.join(iconsSrc, file),
                            path.join(iconsDest, file)
                        );
                    }
                }
            }
        },
    };
}
