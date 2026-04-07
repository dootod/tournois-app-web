package com.example.mobile;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;

import androidx.appcompat.app.AppCompatActivity;

import com.example.mobile.api.ApiClient;

public class MainActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        ApiClient.init(this);
        if (!ApiClient.isLogged()) {
            startActivity(new Intent(this, LoginActivity.class));
            finish();
            return;
        }

        ((Button) findViewById(R.id.btnTournois)).setOnClickListener(
            v -> startActivity(new Intent(this, TournoisActivity.class)));
        ((Button) findViewById(R.id.btnMesTournois)).setOnClickListener(
            v -> startActivity(new Intent(this, MesTournoisActivity.class)));
        ((Button) findViewById(R.id.btnProfile)).setOnClickListener(
            v -> startActivity(new Intent(this, ProfileActivity.class)));
        ((Button) findViewById(R.id.btnScores)).setOnClickListener(
            v -> startActivity(new Intent(this, ScoresActivity.class)));
        ((Button) findViewById(R.id.btnLogout)).setOnClickListener(v -> {
            ApiClient.clear(this);
            startActivity(new Intent(this, LoginActivity.class));
            finish();
        });
    }
}
