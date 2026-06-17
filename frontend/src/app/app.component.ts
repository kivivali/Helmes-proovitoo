import { Component, inject, OnInit } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { SectorService } from './sector.service';
import { FlatSector, Sector } from './sector';

function atLeastOne(c: AbstractControl): { required: true } | null {
  return Array.isArray(c.value) && c.value.length > 0 ? null : { required: true };
}

@Component({
  selector: 'app-root',
  imports: [ReactiveFormsModule],
  templateUrl: './app.component.html',
  styleUrl: './app.component.css',
})
export class AppComponent implements OnInit {
  private fb = inject(FormBuilder);
  private api = inject(SectorService);

  form = this.fb.group({
    name: ['', Validators.required],
    sectorIds: [[] as number[], [atLeastOne]],
    agreeToTerms: [false, Validators.requiredTrue],
  });

  flatSectors: FlatSector[] = [];
  serverErrors: Record<string, string> = {};
  saved = false;
  saveError = '';

  ngOnInit(): void {
    this.api.getSectors().subscribe(tree => {
      this.flatSectors = this.flatten(tree);
    });

    this.api.getSubmission().subscribe({
      next: sub => {
        if (sub) this.form.patchValue(sub);
      },
    });
  }

  save(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.serverErrors = {};
    this.saved = false;
    this.saveError = '';

    this.api.saveSubmission(this.form.getRawValue() as Parameters<SectorService['saveSubmission']>[0]).subscribe({
      next: () => { this.saved = true; },
      error: err => {
        if (err.status === 422) {
          this.serverErrors = err.error.errors ?? {};
        } else {
          this.saveError = 'Unexpected error. Please try again.';
        }
      },
    });
  }

  private flatten(nodes: Sector[], depth = 0): FlatSector[] {
    return nodes.flatMap(n => [
      { id: n.id, name: n.name, depth },
      ...this.flatten(n.children, depth + 1),
    ]);
  }
}
