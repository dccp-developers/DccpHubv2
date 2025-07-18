package ph.edu.dccp.hub;

import android.os.Bundle;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.webkit.WebSettings;
import android.content.Intent;
import android.net.Uri;

import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        // Configure WebView to handle OAuth flows internally
        WebView webView = getBridge().getWebView();
        WebSettings webSettings = webView.getSettings();

        // Enable JavaScript and DOM storage
        webSettings.setJavaScriptEnabled(true);
        webSettings.setDomStorageEnabled(true);
        webSettings.setDatabaseEnabled(true);

        // Enable third-party cookies for OAuth
        webSettings.setMixedContentMode(WebSettings.MIXED_CONTENT_ALWAYS_ALLOW);
        webSettings.setAllowFileAccess(true);
        webSettings.setAllowContentAccess(true);

        // Set user agent to appear as Chrome browser for Google OAuth
        String userAgent = webSettings.getUserAgentString();
        String newUserAgent = userAgent.replace("wv", "").replace("; Version/4.0", "") + " Chrome/120.0.0.0 Mobile Safari/537.36";
        webSettings.setUserAgentString(newUserAgent);

        // Set custom WebViewClient to handle OAuth redirects
        webView.setWebViewClient(new WebViewClient() {
            @Override
            public boolean shouldOverrideUrlLoading(WebView view, String url) {
                Uri uri = Uri.parse(url);
                String host = uri.getHost();

                // Handle OAuth URLs internally
                if (host != null && (
                    host.equals("portal.dccp.edu.ph") ||
                    host.equals("accounts.google.com") ||
                    host.endsWith(".google.com") ||
                    host.endsWith(".googleapis.com") ||
                    host.equals("oauth.googleusercontent.com")
                )) {
                    // Load in the same WebView with proper headers
                    java.util.Map<String, String> headers = new java.util.HashMap<>();
                    headers.put("Sec-Fetch-Site", "same-origin");
                    headers.put("Sec-Fetch-Mode", "navigate");
                    headers.put("Sec-Fetch-Dest", "document");
                    headers.put("Upgrade-Insecure-Requests", "1");
                    view.loadUrl(url, headers);
                    return true;
                }

                // For other URLs, open in external browser
                Intent intent = new Intent(Intent.ACTION_VIEW, uri);
                startActivity(intent);
                return true;
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);

                // Inject JavaScript to handle OAuth popups
                String js =
                    "window.open = function(url, name, specs) {" +
                    "  window.location.href = url;" +
                    "  return window;" +
                    "};";

                view.evaluateJavascript(js, null);
            }
        });
    }
}
