import { TestBed } from '@angular/core/testing';
import { provideHttpClientTesting } from '@angular/common/http/testing';
import { provideHttpClient } from '@angular/common/http';
import { AppComponent } from './app.component';

describe('AppComponent', () => {
  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AppComponent],
      providers: [provideHttpClient(), provideHttpClientTesting()],
    }).compileComponents();
  });

  it('should create the app', () => {
    const fixture = TestBed.createComponent(AppComponent);
    expect(fixture.componentInstance).toBeTruthy();
  });

  it('should render the page heading', () => {
    const fixture = TestBed.createComponent(AppComponent);
    fixture.detectChanges();
    const h1 = fixture.nativeElement.querySelector('h1');
    expect(h1?.textContent).toContain('Sector Registration');
  });

  it('form should be invalid when empty', () => {
    const fixture = TestBed.createComponent(AppComponent);
    expect(fixture.componentInstance.form.invalid).toBeTrue();
  });
});
