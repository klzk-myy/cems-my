#!/usr/bin/env python3
"""
Test Output Analyzer
Analyzes saved test output files and reports issues
"""

import os
import re
import json
from pathlib import Path
from typing import Dict, List, Tuple

TEST_OUTPUT_DIR = Path("/www/wwwroot/local.host/tests/output")

class TestAnalyzer:
    def __init__(self, output_dir: Path = TEST_OUTPUT_DIR):
        self.output_dir = output_dir
        self.results = {}
        
    def analyze_all(self) -> Dict:
        """Analyze all test output files"""
        if not self.output_dir.exists():
            print(f"❌ Output directory not found: {self.output_dir}")
            return {}
            
        files = list(self.output_dir.glob("*.txt"))
        if not files:
            print(f"❌ No test output files found in {self.output_dir}")
            return {}
            
        print(f"\n📁 Analyzing {len(files)} test output files...\n")
        
        for file in files:
            self.results[file.name] = self._analyze_file(file)
            
        return self.results
        
    def _analyze_file(self, filepath: Path) -> Dict:
        """Analyze a single test output file"""
        content = filepath.read_text(encoding='utf-8', errors='ignore')
        
        # Extract test summary
        summary_match = re.search(r'Tests:\s+(\d+)\s+(\w+)', content)
        total_tests = int(summary_match.group(1)) if summary_match else 0
        status = summary_match.group(2) if summary_match else "unknown"
        
        # Extract duration
        duration_match = re.search(r'Duration:\s+([\d.]+)s', content)
        duration = float(duration_match.group(1)) if duration_match else 0.0
        
        # Extract assertions
        assertions_match = re.search(r'(\d+)\s+assertions', content)
        assertions = int(assertions_match.group(1)) if assertions_match else 0
        
        # Find failures
        failures = []
        failure_pattern = re.compile(r'FAIL\s+(.+?)(?=\n[A-Z]|$)', re.DOTALL)
        for match in failure_pattern.finditer(content):
            failure_text = match.group(1).strip()
            # Extract test name
            test_match = re.match(r'^(\S+)', failure_text)
            test_name = test_match.group(1) if test_match else "unknown"
            failures.append({
                'test': test_name,
                'details': failure_text[:500]  # First 500 chars
            })
            
        # Find errors
        errors = []
        error_pattern = re.compile(r'(Error|Exception|Fatal).*?(?=\n\n|\n[A-Z]|$)', re.DOTALL)
        for match in error_pattern.finditer(content):
            error_text = match.group(0).strip()
            errors.append(error_text[:300])
            
        # Count passed tests
        passed_count = len(re.findall(r'✓', content))
        
        return {
            'file': filepath.name,
            'total_tests': total_tests,
            'passed': passed_count,
            'failed': len(failures),
            'errors': len(errors),
            'status': status,
            'duration': duration,
            'assertions': assertions,
            'failures': failures,
            'error_messages': errors[:5]  # First 5 errors
        }
        
    def print_summary(self):
        """Print analysis summary"""
        print("\n" + "="*80)
        print("📊 TEST ANALYSIS SUMMARY")
        print("="*80)
        
        total_passed = 0
        total_failed = 0
        total_errors = 0
        has_failures = False
        
        for filename, result in self.results.items():
            status_icon = "✅" if result['failed'] == 0 else "❌"
            print(f"\n{status_icon} {filename}")
            print(f"   Tests: {result['total_tests']} | "
                  f"Passed: {result['passed']} | "
                  f"Failed: {result['failed']} | "
                  f"Errors: {result['errors']}")
            print(f"   Duration: {result['duration']:.2f}s | "
                  f"Assertions: {result['assertions']}")
            
            total_passed += result['passed']
            total_failed += result['failed']
            total_errors += result['errors']
            
            if result['failed'] > 0 or result['errors'] > 0:
                has_failures = True
                
        print("\n" + "-"*80)
        print(f"TOTAL: {total_passed + total_failed} tests | "
              f"✅ {total_passed} passed | "
              f"❌ {total_failed} failed | "
              f"⚠️ {total_errors} errors")
        print("="*80)
        
        if has_failures:
            self._print_failures()
            
        return not has_failures
        
    def _print_failures(self):
        """Print detailed failure information"""
        print("\n🔍 FAILURE DETAILS:")
        print("-"*80)
        
        for filename, result in self.results.items():
            if result['failures']:
                print(f"\n📄 {filename}:")
                for i, failure in enumerate(result['failures'][:3], 1):  # First 3
                    print(f"  {i}. {failure['test']}")
                    print(f"     {failure['details'][:200]}...")
                    
            if result['error_messages']:
                print(f"\n⚠️ Errors in {filename}:")
                for error in result['error_messages'][:2]:  # First 2
                    print(f"   - {error[:100]}...")
                    
    def get_failed_tests(self) -> List[str]:
        """Get list of failed test names"""
        failed = []
        for result in self.results.values():
            for failure in result['failures']:
                failed.append(failure['test'])
        return failed
        
    def export_json(self, filepath: str = "test-analysis.json"):
        """Export analysis to JSON"""
        output_path = self.output_dir / filepath
        output_path.write_text(json.dumps(self.results, indent=2))
        print(f"\n💾 Analysis saved to: {output_path}")

def main():
    analyzer = TestAnalyzer()
    analyzer.analyze_all()
    success = analyzer.print_summary()
    analyzer.export_json()
    
    # Return exit code
    return 0 if success else 1

if __name__ == "__main__":
    exit(main())
