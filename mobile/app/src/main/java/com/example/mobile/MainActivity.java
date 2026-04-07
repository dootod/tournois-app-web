package com.example.mobile;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;

import androidx.appcompat.app.AppCompatActivity;

import com.example.mobile.api.ApiClient;

public class MainActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        if (getSupportActionBar() != null) getSupportActionBar().hide();

        ApiClient.init(this);
        if (!ApiClient.isLogged()) {
            startActivity(new Intent(this, LoginActivity.class));
            finish();
            return;
        }

        findViewById(R.id.btnTournois).setOnClickListener(
            v -> startActivity(new Intent(this, TournoisActivity.class)));
        findViewById(R.id.btnMesTournois).setOnClickListener(
            v -> startActivity(new Intent(this, MesTournoisActivity.class)));
        findViewById(R.id.btnProfile).setOnClickListener(
            v -> startActivity(new Intent(this, ProfileActivity.class)));
        findViewById(R.id.btnScores).setOnClickListener(
            v -> startActivity(new Intent(this, ScoresActivity.class)));
        findViewById(R.id.btnLogout).setOnClickListener(v -> {
            ApiClient.clear(this);
            startActivity(new Intent(this, LoginActivity.class));
            finish();
        });
    }
}
