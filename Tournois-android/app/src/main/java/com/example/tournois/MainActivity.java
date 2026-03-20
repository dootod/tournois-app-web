package com.example.tournois;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.TextUtils;
import android.util.Patterns;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.EdgeToEdge;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;
import com.google.android.material.textfield.TextInputLayout;

import org.json.JSONObject;

import java.io.IOException;

import okhttp3.Call;
import okhttp3.Callback;
import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

public class MainActivity extends AppCompatActivity {

    // ── URL de base de votre API ─────────────────────────────────────────────
    private static final String API_BASE_URL = "https://votre-api.com"; // TODO: remplacer
    private static final String LOGIN_ENDPOINT = API_BASE_URL + "/api/auth/login";
    private static final String PREFS_NAME = "judo_prefs";
    private static final String KEY_TOKEN = "auth_token";
    // ────────────────────────────────────────────────────────────────────────

    private TextInputLayout tilEmail, tilPassword;
    private TextInputEditText etEmail, etPassword;
    private MaterialButton btnLogin;
    private ProgressBar progressBar;
    private TextView tvError;

    private OkHttpClient httpClient;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);

        ViewCompat.setOnApplyWindowInsetsListener(findViewById(R.id.main), (v, insets) -> {
            Insets systemBars = insets.getInsets(WindowInsetsCompat.Type.systemBars());
            v.setPadding(systemBars.left, systemBars.top, systemBars.right, systemBars.bottom);
            return insets;
        });

        // Si déjà connecté, rediriger directement
        if (isAlreadyLoggedIn()) {
            navigateToDashboard();
            return;
        }

        initViews();
        initHttpClient();
        setupListeners();
    }

    // ── Initialisation des vues ──────────────────────────────────────────────

    private void initViews() {
        tilEmail        = findViewById(R.id.til_email);
        tilPassword     = findViewById(R.id.til_password);
        etEmail         = findViewById(R.id.et_email);
        etPassword      = findViewById(R.id.et_password);
        btnLogin        = findViewById(R.id.btn_login);
        progressBar     = findViewById(R.id.progress_bar);
        tvError         = findViewById(R.id.tv_error);
    }

    private void initHttpClient() {
        httpClient = new OkHttpClient.Builder()
                .connectTimeout(15, java.util.concurrent.TimeUnit.SECONDS)
                .readTimeout(15, java.util.concurrent.TimeUnit.SECONDS)
                .build();
    }

    private void setupListeners() {
        btnLogin.setOnClickListener(v -> attemptLogin());

        // Effacer les erreurs quand l'utilisateur retape
        etEmail.setOnFocusChangeListener((v, hasFocus) -> {
            if (hasFocus) tilEmail.setError(null);
        });
        etPassword.setOnFocusChangeListener((v, hasFocus) -> {
            if (hasFocus) tilPassword.setError(null);
        });
    }

    // ── Validation & Appel API ───────────────────────────────────────────────

    private void attemptLogin() {
        // Récupérer les valeurs
        String email    = etEmail.getText() != null ? etEmail.getText().toString().trim() : "";
        String password = etPassword.getText() != null ? etPassword.getText().toString() : "";

        // Réinitialiser les erreurs
        tilEmail.setError(null);
        tilPassword.setError(null);
        hideError();

        // Validation locale
        if (!validateInputs(email, password)) return;

        // Appel API
        setLoading(true);
        callLoginApi(email, password);
    }

    private boolean validateInputs(String email, String password) {
        boolean valid = true;

        if (TextUtils.isEmpty(email)) {
            tilEmail.setError("L'email est requis");
            valid = false;
        } else if (!Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            tilEmail.setError("Email invalide");
            valid = false;
        }

        if (TextUtils.isEmpty(password)) {
            tilPassword.setError("Le mot de passe est requis");
            valid = false;
        } else if (password.length() < 6) {
            tilPassword.setError("Minimum 6 caractères");
            valid = false;
        }

        return valid;
    }

    private void callLoginApi(String email, String password) {
        try {
            // Construire le corps JSON
            JSONObject jsonBody = new JSONObject();
            jsonBody.put("email", email);
            jsonBody.put("password", password);

            RequestBody body = RequestBody.create(
                    jsonBody.toString(),
                    MediaType.parse("application/json; charset=utf-8")
            );

            Request request = new Request.Builder()
                    .url(LOGIN_ENDPOINT)
                    .post(body)
                    .build();

            httpClient.newCall(request).enqueue(new Callback() {
                @Override
                public void onFailure(Call call, IOException e) {
                    runOnUiThread(() -> {
                        setLoading(false);
                        showError("Erreur réseau. Vérifiez votre connexion.");
                    });
                }

                @Override
                public void onResponse(Call call, Response response) throws IOException {
                    String responseBody = response.body() != null ? response.body().string() : "";

                    runOnUiThread(() -> {
                        setLoading(false);
                        handleApiResponse(response.code(), responseBody);
                    });
                }
            });

        } catch (Exception e) {
            setLoading(false);
            showError("Erreur inattendue. Réessayez.");
        }
    }

    private void handleApiResponse(int statusCode, String responseBody) {
        try {
            JSONObject json = new JSONObject(responseBody);

            if (statusCode == 200) {
                // Succès — récupérer le token
                String token = json.optString("token", "");

                if (!TextUtils.isEmpty(token)) {
                    saveToken(token);
                    navigateToDashboard();
                } else {
                    showError("Réponse invalide du serveur.");
                }

            } else if (statusCode == 401) {
                showError("Email ou mot de passe incorrect.");

            } else if (statusCode == 429) {
                showError("Trop de tentatives. Réessayez plus tard.");

            } else {
                String message = json.optString("message", "Erreur serveur (" + statusCode + ").");
                showError(message);
            }

        } catch (Exception e) {
            showError("Réponse inattendue du serveur.");
        }
    }

    // ── Navigation ───────────────────────────────────────────────────────────

    private void navigateToDashboard() {
        // TODO: remplacer DashboardActivity par votre activité principale
        Intent intent = new Intent(this, DashboardActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
        finish();
    }

    // ── Token / Session ──────────────────────────────────────────────────────

    private void saveToken(String token) {
        SharedPreferences prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE);
        prefs.edit().putString(KEY_TOKEN, token).apply();
    }

    private boolean isAlreadyLoggedIn() {
        SharedPreferences prefs = getSharedPreferences(PREFS_NAME, MODE_PRIVATE);
        String token = prefs.getString(KEY_TOKEN, "");
        return !TextUtils.isEmpty(token);
    }

    // ── UI Helpers ───────────────────────────────────────────────────────────

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        btnLogin.setEnabled(!loading);
        btnLogin.setText(loading ? "" : "SE CONNECTER");
        etEmail.setEnabled(!loading);
        etPassword.setEnabled(!loading);
    }

    private void showError(String message) {
        tvError.setText(message);
        tvError.setVisibility(View.VISIBLE);
    }

    private void hideError() {
        tvError.setVisibility(View.GONE);
        tvError.setText("");
    }
}